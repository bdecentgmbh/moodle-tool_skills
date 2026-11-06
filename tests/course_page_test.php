<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tool skills - Tests of the Skills page of a course and its tab.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/skills/lib.php');

#[\PHPUnit\Framework\Attributes\CoversClass(helper::class)]
#[\PHPUnit\Framework\Attributes\CoversFunction('tool_skills_extend_navigation_course')]
/**
 * The Skills page of a course shows its skills, and learners their progress.
 *
 * @covers \tool_skills\helper::can_view_course_page
 * @covers \tool_skills\helper::get_award_label
 * @covers \tool_skills\helper::get_user_profile_skills_context
 * @covers \tool_skills\points_history::get_course_points
 * @covers ::tool_skills_extend_navigation_course
 */
final class course_page_test extends \advanced_testcase {
    /**
     * Create a skill with two levels.
     *
     * @param string $name
     * @return int The skill id.
     */
    private function create_skill(string $name): int {
        global $DB;

        $skillid = (int) $DB->insert_record('tool_skills', (object) [
            'name' => $name, 'identitykey' => strtolower($name), 'description' => '', 'status' => 1,
            'categories' => '[]', 'learningtime' => 0, 'levelscount' => 2, 'archived' => 0, 'timearchived' => 0,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        foreach (['Basic' => 30, 'Advanced' => 60] as $level => $points) {
            $DB->insert_record('tool_skills_levels', (object) [
                'skill' => $skillid, 'name' => $level, 'points' => $points, 'status' => 1,
                'timecreated' => time(), 'timemodified' => time(),
            ]);
        }
        return $skillid;
    }

    /**
     * Enable a skill in a course.
     *
     * @param int $courseid
     * @param int $skillid
     * @param int $points Points that course completion awards.
     * @return void
     */
    private function enable_skill(int $courseid, int $skillid, int $points): void {
        global $DB;

        $DB->insert_record('tool_skills_courses', (object) [
            'courseid' => $courseid, 'skill' => $skillid, 'status' => 1,
            'uponcompletion' => skills::COMPLETIONPOINTS, 'points' => $points, 'level' => 0, 'timemodified' => time(),
        ]);
    }

    /**
     * Whether the course navigation has the Skills tab, and its URL.
     *
     * @param \stdClass $course
     * @return string|null The URL of the tab, null without a tab.
     */
    private function get_tab_url(\stdClass $course): ?string {
        $navigation = \navigation_node::create('Course');
        tool_skills_extend_navigation_course($navigation, $course, \context_course::instance($course->id));
        $node = $navigation->find('manage-tool-skills', \navigation_node::TYPE_SETTING);
        return $node ? $node->action->out(false) : null;
    }

    /**
     * Learners get the tab only when the course awards a skill and the profile section is not disabled;
     * teachers always get it, to enable skills.
     */
    public function test_tab(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'editingteacher');

        $this->setUser($student);
        $this->assertNull($this->get_tab_url($course));
        $this->setUser($teacher);
        $this->assertEquals(
            (new \moodle_url('/admin/tool/skills/course.php', ['courseid' => $course->id]))->out(false),
            $this->get_tab_url($course)
        );

        $this->enable_skill($course->id, $this->create_skill('Safety'), 40);
        $this->setUser($student);
        $this->assertNotNull($this->get_tab_url($course));

        set_config('profiledisplay', helper::PROFILEDISPLAY_DISABLED, 'tool_skills');
        $this->assertNull($this->get_tab_url($course));
        $this->setUser($teacher);
        $this->assertNotNull($this->get_tab_url($course));
    }

    /**
     * A disabled or archived skill does not count as a skill the course awards.
     */
    public function test_disabled_and_archived_skills_do_not_count(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $this->setUser($gen->create_and_enrol($course, 'student'));
        $context = \context_course::instance($course->id);
        $skillid = $this->create_skill('Safety');
        $this->enable_skill($course->id, $skillid, 40);

        $this->assertTrue(helper::can_view_course_page($context));
        $DB->set_field('tool_skills', 'archived', 1, ['id' => $skillid]);
        $this->assertFalse(helper::can_view_course_page($context));
        $DB->set_field('tool_skills', 'archived', 0, ['id' => $skillid]);
        $DB->set_field('tool_skills', 'status', 0, ['id' => $skillid]);
        $this->assertFalse(helper::can_view_course_page($context));
    }

    /**
     * Awards in the words of the course and activity skill lists.
     */
    public function test_award_label(): void {
        global $DB;
        $this->resetAfterTest();
        $skillid = $this->create_skill('Safety');
        $levelid = (int) $DB->get_field('tool_skills_levels', 'id', ['skill' => $skillid, 'name' => 'Advanced']);

        $this->assertEquals('Points - 40', helper::get_award_label(skills::COMPLETIONPOINTS, 40, null));
        $this->assertEquals('Points by grade', helper::get_award_label(skills::COMPLETIONPOINTSGRADE, null, null));
        $this->assertEquals('Set level - Advanced', helper::get_award_label(skills::COMPLETIONSETLEVEL, null, $levelid));
        $this->assertEquals('Force level - Advanced', helper::get_award_label(skills::COMPLETIONFORCELEVEL, null, $levelid));
        $this->assertEquals('', helper::get_award_label(skills::COMPLETIONNOTHING, null, null));
    }

    /**
     * A learner sees the course's skills with their points, the points from the course and what the course
     * awards; skills of other courses are left out.
     */
    public function test_learner_context(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $other = $gen->create_course();
        $student = $gen->create_and_enrol($course, 'student');
        $gen->enrol_user($student->id, $other->id, 'student');
        $skillid = $this->create_skill('Safety');
        $this->enable_skill($course->id, $skillid, 40);
        $this->enable_skill($other->id, $this->create_skill('Forklift'), 10);

        // 40 points from this course and 5 from another.
        $skill = skills::get($skillid);
        $skill->set_userskill_points($student->id, 40, new points_change(points_history::REASON_AWARD, 'course', 1, $course->id));
        $skill->set_userskill_points($student->id, 45, new points_change(points_history::REASON_AWARD, 'course', 2, $other->id));

        $this->setUser($student);
        $context = helper::get_user_profile_skills_context($student->id, true, $course->id);

        $this->assertCount(1, $context['skills']);
        $card = $context['skills'][0];
        $this->assertEquals('Safety', $card['name']);
        $this->assertEquals(45, $card['earned']);
        $this->assertFalse($card['hidepoints']);
        $this->assertTrue($card['hascoursepoints']);
        $this->assertEquals(40, $card['coursepoints']);
        $this->assertCount(1, $card['courses']);
        $this->assertEquals('Points - 40', $card['courses'][0]['award']);
        $this->assertTrue($card['courses'][0]['hasawards']);

        $html = $this->render_page($context);
        $this->assertStringContainsString('40 points from this course', $html);
        $this->assertStringContainsString('Earn it in this course', $html);
        $this->assertStringContainsString('Course completion', $html);
        $this->assertStringContainsString('toolskill-earned', $html);
        $this->assertStringNotContainsString('Forklift', $html);
    }

    /**
     * Without a learner, the cards show what the course awards, without points or pills.
     */
    public function test_context_without_learner(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $this->enable_skill($course->id, $this->create_skill('Safety'), 40);
        $this->setAdminUser();

        $context = helper::get_user_profile_skills_context(0, true, $course->id);

        $card = $context['skills'][0];
        $this->assertTrue($card['hidepoints']);
        $this->assertFalse($card['hascoursepoints']);
        $this->assertFalse($card['haspills']);
        $this->assertEquals('Points - 40', $card['courses'][0]['award']);

        $html = $this->render_page($context);
        $this->assertStringContainsString('Points - 40', $html);
        $this->assertStringNotContainsString('toolskill-earned', $html);
        $this->assertStringNotContainsString('toolskill-chip', $html);
    }

    /**
     * A skill whose course completion awards nothing has no award rows.
     */
    public function test_nothing_awarded(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $skillid = $this->create_skill('Safety');
        $this->enable_skill($course->id, $skillid, 0);
        $DB->set_field('tool_skills_courses', 'uponcompletion', skills::COMPLETIONNOTHING, ['skill' => $skillid]);
        $this->setAdminUser();

        $context = helper::get_user_profile_skills_context(0, true, $course->id);

        $this->assertFalse($context['skills'][0]['courses'][0]['hasawards']);
        $this->assertStringNotContainsString('Earn it in this course', $this->render_page($context));
    }

    /**
     * Render the cards of the course's Skills page.
     *
     * @param array $context
     * @return string
     */
    private function render_page(array $context): string {
        global $OUTPUT;
        return $OUTPUT->render_from_template('tool_skills/course_skills', $context);
    }
}
