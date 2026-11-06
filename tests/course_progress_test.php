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
 * Tool skills - Tests of the Participants' progress page of a course and the course navigation.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use tool_skills\table\course_participants_table;

#[\PHPUnit\Framework\Attributes\CoversClass(course_participants_table::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(helper::class)]
/**
 * Teachers follow the participants' progress of their course, one skill at a time.
 *
 * @covers \tool_skills\table\course_participants_table
 * @covers \tool_skills\helper::can_view_course_participants
 * @covers \tool_skills\helper::get_course_navigation
 * @covers \tool_skills\helper::get_course_skills
 * @covers \tool_skills\helper::get_course_level_counts
 */
final class course_progress_test extends \advanced_testcase {
    /**
     * Create a skill with the levels Basic (30) and Advanced (60), enabled in the given courses.
     *
     * @param string $name
     * @param array $courseids
     * @return skills
     */
    private function create_skill(string $name, array $courseids): skills {
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
        foreach ($courseids as $courseid) {
            $DB->insert_record('tool_skills_courses', (object) [
                'courseid' => $courseid, 'skill' => $skillid, 'status' => 1,
                'uponcompletion' => skills::COMPLETIONPOINTS, 'points' => 40, 'level' => 0, 'timemodified' => time(),
            ]);
        }
        return skills::get($skillid);
    }

    /**
     * The rows of the table, keyed by user id.
     *
     * @param \stdClass $course
     * @param skills $skill
     * @param int $groupid
     * @param bool $showtotals
     * @return array [course_participants_table $table, \stdClass[] $rows]
     */
    private function get_rows(\stdClass $course, skills $skill, int $groupid, bool $showtotals): array {
        $table = new course_participants_table(\context_course::instance($course->id), $skill, $groupid, $showtotals);
        $table->define_baseurl(new \moodle_url('/admin/tool/skills/progress.php', ['courseid' => $course->id]));
        $table->setup();
        $table->query_db(50, false);
        return [$table, $table->rawdata];
    }

    /**
     * The table lists the course's learners, without teachers, with their total points, their level and
     * the points from this course.
     */
    public function test_rows(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $other = $gen->create_course();
        $anna = $gen->create_and_enrol($course, 'student');
        $ben = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $skill = $this->create_skill('Safety', [$course->id, $other->id]);
        $skill->set_userskill_points($anna->id, 40, new points_change(points_history::REASON_AWARD, 'course', 1, $course->id));
        $skill->set_userskill_points($anna->id, 45, new points_change(points_history::REASON_AWARD, 'course', 2, $other->id));
        $this->setUser($teacher);

        [$table, $rows] = $this->get_rows($course, $skill, 0, true);

        $this->assertEqualsCanonicalizing([$anna->id, $ben->id], array_keys($rows));
        $this->assertEquals([45, 40], [(int) $rows[$anna->id]->points, (int) $rows[$anna->id]->coursepoints]);
        $this->assertEquals([0, 0], [(int) $rows[$ben->id]->points, (int) $rows[$ben->id]->coursepoints]);
        $this->assertEquals('Basic', $table->col_level($rows[$anna->id]));
        $this->assertEquals('', $table->col_level($rows[$ben->id]));
        $this->assertEquals(['fullname', 'level', 'points', 'coursepoints'], array_keys($table->columns));
    }

    /**
     * Without the totals the table has only the points from this course; a group shows only its members.
     */
    public function test_course_points_only_and_groups(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $anna = $gen->create_and_enrol($course, 'student');
        $ben = $gen->create_and_enrol($course, 'student');
        $group = $gen->create_group(['courseid' => $course->id]);
        $gen->create_group_member(['groupid' => $group->id, 'userid' => $ben->id]);
        $skill = $this->create_skill('Safety', [$course->id]);
        $this->setAdminUser();

        [$table, $rows] = $this->get_rows($course, $skill, $group->id, false);

        $this->assertEquals([$ben->id], array_keys($rows));
        $this->assertEquals(['fullname', 'coursepoints'], array_keys($table->columns));
    }

    /**
     * Teachers open the progress page and get the course navigation; learners get neither. A role that
     * manages the course's skills without the totals capability still opens the page.
     */
    public function test_access_and_navigation(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $context = \context_course::instance($course->id);
        $student = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'teacher');
        $this->create_skill('Safety', [$course->id]);

        $this->setUser($student);
        $this->assertFalse(helper::can_view_course_participants($context));
        $this->assertSame('', helper::get_course_navigation($context, 'overview'));

        $this->setUser($teacher);
        $this->assertTrue(helper::can_view_course_participants($context));
        $html = helper::get_course_navigation($context, 'progress');
        $this->assertStringContainsString('Overview', $html);
        $this->assertStringContainsString('Participants&#039; progress', $html);
        $this->assertStringContainsString('/admin/tool/skills/manage/courselist.php', $html);
        // The current page is the page heading.
        $this->assertStringContainsString('aria-level="2"', $html);

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        assign_capability('tool/skills:viewcourseparticipants', CAP_PROHIBIT, $roleid, $context);
        $this->assertTrue(helper::can_view_course_participants($context));
        assign_capability('tool/skills:managecourseskillslist', CAP_PROHIBIT, $roleid, $context);
        $this->assertFalse(helper::can_view_course_participants($context));
    }

    /**
     * The skills a course awards, by name, without disabled or archived skills.
     */
    public function test_course_skills(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $safety = $this->create_skill('Safety', [$course->id]);
        $firstaid = $this->create_skill('First aid', [$course->id]);
        $archived = $this->create_skill('Archived', [$course->id]);
        $DB->set_field('tool_skills', 'archived', 1, ['id' => $archived->get_id()]);

        $this->assertSame(
            [$firstaid->get_id() => 'First aid', $safety->get_id() => 'Safety'],
            helper::get_course_skills($course->id)
        );
    }

    /**
     * The teachers' overview counts the learners per level, without teachers; in separate groups only the
     * members of the teacher's groups, and nothing for a skill without levels.
     */
    public function test_level_counts(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1]);
        $context = \context_course::instance($course->id);
        $teacher = $gen->create_and_enrol($course, 'teacher');
        $skill = $this->create_skill('Safety', [$course->id]);
        $group = $gen->create_group(['courseid' => $course->id]);
        $gen->create_group_member(['groupid' => $group->id, 'userid' => $teacher->id]);
        foreach (['anna' => 45, 'ben' => 70, 'chloe' => null] as $name => $points) {
            $user = $gen->create_and_enrol($course, 'student', ['firstname' => $name]);
            if ($points !== null) {
                $skill->set_userskill_points($user->id, $points);
            }
            if ($name === 'ben') {
                $gen->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);
            }
        }
        $skill->set_userskill_points($teacher->id, 100);
        $this->setUser($teacher);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);

        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $roleid, $context);
        $counts = helper::get_course_level_counts($context, $skill);
        $this->assertSame(['No level: 1', 'Basic: 1', 'Advanced: 1'], array_column($counts, 'label'));

        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $roleid, $context, true);
        $counts = helper::get_course_level_counts($context, $skill);
        $this->assertSame(['No level: 0', 'Basic: 0', 'Advanced: 1'], array_column($counts, 'label'));

        $DB->delete_records('tool_skills_levels', ['skill' => $skill->get_id()]);
        $this->assertSame([], helper::get_course_level_counts($context, skills::get($skill->get_id())));
    }
}
