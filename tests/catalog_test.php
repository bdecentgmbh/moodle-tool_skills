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
 * Tool skills - Tests of the Courses for skills page.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use tool_skills\table\catalog_table;

#[\PHPUnit\Framework\Attributes\CoversClass(catalog_table::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(helper::class)]
/**
 * Learners find the courses that award skills, among the courses they see elsewhere in Moodle.
 *
 * @covers \tool_skills\table\catalog_table
 * @covers \tool_skills\helper::can_browse_courses
 * @covers \tool_skills\helper::get_course_skills
 */
final class catalog_test extends \advanced_testcase {
    /** @var \stdClass[] The courses, by name. */
    private array $courses = [];

    /** @var int[] The skill ids, by name. */
    private array $skills = [];

    /**
     * Courses that award skills: Fire and First aid, a hidden course, a course whose skill is switched off
     * and an archived skill.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        foreach (['Fire course' => 1, 'First aid course' => 1, 'Hidden course' => 0, 'Other course' => 1] as $name => $visible) {
            $this->courses[$name] = $gen->create_course(['fullname' => $name, 'visible' => $visible]);
        }
        foreach (['Safety' => 0, 'First aid' => 0, 'Old' => 1] as $name => $archived) {
            $this->skills[$name] = (int) $DB->insert_record('tool_skills', (object) [
                'name' => $name, 'identitykey' => strtolower($name), 'description' => '', 'status' => 1,
                'categories' => '[]', 'learningtime' => 0, 'levelscount' => 1, 'archived' => $archived, 'timearchived' => 0,
                'timecreated' => time(), 'timemodified' => time(),
            ]);
        }
        $basic = $DB->insert_record('tool_skills_levels', (object) [
            'skill' => $this->skills['Safety'], 'name' => 'Basic', 'points' => 30, 'status' => 1,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $awards = [
            ['Fire course', 'Safety', 1, skills::COMPLETIONPOINTS, 40, 0],
            ['First aid course', 'Safety', 1, skills::COMPLETIONSETLEVEL, 0, $basic],
            ['First aid course', 'First aid', 1, skills::COMPLETIONNOTHING, 0, 0],
            ['Hidden course', 'Safety', 1, skills::COMPLETIONPOINTS, 10, 0],
            ['Other course', 'Safety', 0, skills::COMPLETIONPOINTS, 10, 0],
            ['Fire course', 'Old', 1, skills::COMPLETIONPOINTS, 10, 0],
        ];
        foreach ($awards as [$course, $skill, $status, $type, $points, $level]) {
            $DB->insert_record('tool_skills_courses', (object) [
                'courseid' => $this->courses[$course]->id, 'skill' => $this->skills[$skill], 'status' => $status,
                'uponcompletion' => $type, 'points' => $points, 'level' => $level, 'timemodified' => time(),
            ]);
        }
    }

    /**
     * The table of the current user.
     *
     * @param int $skillid
     * @param int $pagesize
     * @return catalog_table
     */
    private function get_table(int $skillid = 0, int $pagesize = 50): catalog_table {
        $table = new catalog_table($skillid);
        $table->define_baseurl(new \moodle_url('/admin/tool/skills/catalog.php'));
        $table->setup();
        $table->query_db($pagesize, false);
        return $table;
    }

    /**
     * The course and skill of each row.
     *
     * @param catalog_table $table
     * @return string[]
     */
    private function get_rows(catalog_table $table): array {
        return array_values(array_map(fn($row) => "$row->coursename: $row->skillname", $table->rawdata));
    }

    /**
     * Learners see the courses awarding enabled skills that they see in the course listings; with
     * moodle/course:viewhiddencourses also the hidden ones. Courses that award nothing in a skill are left out.
     */
    public function test_rows_follow_course_visibility(): void {
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);

        $table = $this->get_table();
        $this->assertSame(['Fire course: Safety', 'First aid course: Safety'], $this->get_rows($table));
        $this->assertSame(2, $table->totalrows);
        $this->assertCount(1, $this->get_table(0, 1)->rawdata);

        $this->setAdminUser();
        $rows = $this->get_rows($this->get_table());
        $this->assertContains('Hidden course: Safety', $rows);
        $this->assertNotContains('Other course: Safety', $rows);
        $this->assertNotContains('Fire course: Old', $rows);
    }

    /**
     * One skill shows only its courses; the skills to choose from are those enabled in any course.
     */
    public function test_skill(): void {
        $this->setAdminUser();
        $this->assertSame(
            ['Fire course: Safety', 'First aid course: Safety', 'Hidden course: Safety'],
            $this->get_rows($this->get_table($this->skills['Safety']))
        );
        $this->assertSame([], $this->get_rows($this->get_table($this->skills['First aid'])));
        $this->assertSame(
            [$this->skills['First aid'] => 'First aid', $this->skills['Safety'] => 'Safety'],
            helper::get_course_skills(0)
        );
    }

    /**
     * Each row says what the course awards, and whether the user has completed the course, is enrolled in
     * it or how to enrol.
     */
    public function test_awards_and_status(): void {
        global $DB;
        $gen = $this->getDataGenerator();
        $student = $gen->create_user();
        $gen->enrol_user($student->id, $this->courses['Fire course']->id);
        $DB->insert_record('course_completions', (object) [
            'userid' => $student->id, 'course' => $this->courses['First aid course']->id, 'timecompleted' => time(),
        ]);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['enrol' => 'self']);
        $this->setUser($student);

        $table = $this->get_table();
        $rows = array_values($table->rawdata);

        $this->assertStringContainsString('Course completion · Points - 40', $table->col_awards($rows[0]));
        $this->assertStringContainsString('Course completion · Set level - Basic', $table->col_awards($rows[1]));
        $this->assertSame('Enrolled', $table->col_status($rows[0]));
        $this->assertSame('Completed', $table->col_status($rows[1]));

        $DB->delete_records('course_completions');
        $table = $this->get_table();
        $this->assertStringContainsString('Self enrolment', $table->col_status(array_values($table->rawdata)[1]));
    }

    /**
     * Authenticated users open the page, unless the Skills section of the profile is disabled.
     */
    public function test_access(): void {
        global $DB;

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertTrue(helper::can_browse_courses());

        set_config('profiledisplay', helper::PROFILEDISPLAY_DISABLED, 'tool_skills');
        $this->assertFalse(helper::can_browse_courses());
        set_config('profiledisplay', helper::PROFILEDISPLAY_TOTALS, 'tool_skills');

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('tool/skills:browsecourses', CAP_PROHIBIT, $roleid, \context_system::instance(), true);
        $this->assertFalse(helper::can_browse_courses());

        $this->setGuestUser();
        $this->assertFalse(helper::can_browse_courses());
    }
}
