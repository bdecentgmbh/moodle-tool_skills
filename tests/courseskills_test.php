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
 * Tool Skills - PHPUnit tests for the courseskills class.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Unit tests for \tool_skills\courseskills.
 *
 * @covers \tool_skills\courseskills
 */
final class courseskills_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Insert a minimal skill record and return its id.
     *
     * @return int
     */
    private function create_skill(): int {
        global $DB;
        static $n = 0;
        $n++;
        return $DB->insert_record('tool_skills', (object)[
            'name'         => 'Skill ' . $n,
            'identitykey'  => 'csk' . $n,
            'description'  => '',
            'status'       => 1,
            'categories'   => '[]',
            'learningtime' => '',
            'levelscount'  => 0,
            'archived'     => 0,
            'timearchived' => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);
    }

    private function create_level(int $skillid, int $points): int {
        global $DB;
        return $DB->insert_record('tool_skills_levels', (object)[
            'skill'        => $skillid,
            'name'         => 'L' . $points,
            'points'       => $points,
            'status'       => 1,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Insert a tool_skills_courses record and return its id.
     */
    private function assign_skill_to_course(
        int $courseid,
        int $skillid,
        int $status = 1,
        int $uponcompletion = 1,
        int $points = 50,
        int $level = 0
    ): int {
        global $DB;
        return $DB->insert_record('tool_skills_courses', (object)[
            'courseid'       => $courseid,
            'skill'          => $skillid,
            'status'         => $status,
            'uponcompletion' => $uponcompletion,
            'points'         => $points,
            'level'          => $level,
            'timemodified'   => time(),
        ]);
    }

    /**
     * Mark a course complete for a user via the completion API.
     */
    private function complete_course(\stdClass $course, \stdClass $user): void {
        $cinfo = new \completion_info($course);
        $cinfo->mark_course_completions([$user->id]);
    }

    // -------------------------------------------------------------------------
    // Instance skill lists
    // -------------------------------------------------------------------------

    /**
     * Test get_instance_skills() returns only enabled skills for the course.
     */
    public function test_get_instance_skills_returns_enabled_skills(): void {
        $course = $this->getDataGenerator()->create_course();
        $skill1 = $this->create_skill();
        $skill2 = $this->create_skill();
        $skill3 = $this->create_skill();
        $this->assign_skill_to_course($course->id, $skill1, 1);
        $this->assign_skill_to_course($course->id, $skill2, 1);
        $this->assign_skill_to_course($course->id, $skill3, 0); // disabled

        $result = courseskills::get($course->id)->get_instance_skills();
        $this->assertCount(2, $result);
    }

    /**
     * Test get_instance_disabled_skills() returns only disabled skills for the course.
     */
    public function test_get_instance_disabled_skills_returns_disabled_only(): void {
        $course = $this->getDataGenerator()->create_course();
        $skill1 = $this->create_skill();
        $skill2 = $this->create_skill();
        $this->assign_skill_to_course($course->id, $skill1, 1);
        $this->assign_skill_to_course($course->id, $skill2, 0);

        $result = courseskills::get($course->id)->get_instance_disabled_skills();
        $this->assertCount(1, $result);
    }

    // -------------------------------------------------------------------------
    // remove_instance_skills
    // -------------------------------------------------------------------------

    /**
     * Test remove_instance_skills() clears all skill assignments for a course.
     */
    public function test_remove_instance_skills_clears_course_skills(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $skill1 = $this->create_skill();
        $skill2 = $this->create_skill();
        $this->assign_skill_to_course($course->id, $skill1);
        $this->assign_skill_to_course($course->id, $skill2);

        courseskills::get($course->id)->remove_instance_skills();

        $this->assertEquals(0, $DB->count_records('tool_skills_courses', ['courseid' => $course->id]));
    }

    // -------------------------------------------------------------------------
    // manage_course_completions
    // -------------------------------------------------------------------------

    /**
     * Test COMPLETIONPOINTS strategy awards the configured points when course is completed.
     */
    public function test_manage_course_completions_awards_points_on_completion(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user   = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $skillid = $this->create_skill();
        $this->assign_skill_to_course($course->id, $skillid, 1, skills::COMPLETIONPOINTS, 50);

        $cs = courseskills::get($course->id);
        $skills = $cs->get_instance_skills();
        $cs->manage_course_completions($user->id, $skills, null);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(50, $points);
    }

    /**
     * Test COMPLETIONSETLEVEL strategy awards exactly the level's threshold points.
     */
    public function test_manage_course_completions_set_level_awards_level_points(): void {
        global $DB;
        $course  = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user    = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $skillid = $this->create_skill();
        $levelid = $this->create_level($skillid, 80);
        $this->assign_skill_to_course($course->id, $skillid, 1, skills::COMPLETIONSETLEVEL, 0, $levelid);

        $cs = courseskills::get($course->id);
        $cs->manage_course_completions($user->id, $cs->get_instance_skills(), null);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(80, $points);
    }

    /**
     * Test COMPLETIONFORCELEVEL strategy overwrites user points with the level threshold.
     */
    public function test_manage_course_completions_force_level_overwrites_points(): void {
        global $DB;
        $course  = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user    = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $skillid = $this->create_skill();
        $levelid = $this->create_level($skillid, 80);

        // Pre-award 200 pts so user is above the level.
        $DB->insert_record('tool_skills_userpoints', (object)[
            'skill' => $skillid, 'userid' => $user->id, 'points' => 200,
            'timecreated' => time(), 'timemodified' => time(),
        ]);

        $this->assign_skill_to_course($course->id, $skillid, 1, skills::COMPLETIONFORCELEVEL, 0, $levelid);

        $cs = courseskills::get($course->id);
        $cs->manage_course_completions($user->id, $cs->get_instance_skills(), null);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(80, $points);
    }

    /**
     * Test COMPLETIONNOTHING strategy creates no userpoints record.
     */
    public function test_manage_course_completions_nothing_awards_zero_points(): void {
        global $DB;
        $course  = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user    = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $skillid = $this->create_skill();
        $this->assign_skill_to_course($course->id, $skillid, 1, skills::COMPLETIONNOTHING, 0);

        $cs = courseskills::get($course->id);
        $cs->manage_course_completions($user->id, $cs->get_instance_skills(), null);

        $this->assertFalse($DB->record_exists('tool_skills_userpoints', ['skill' => $skillid, 'userid' => $user->id]));
    }

    // -------------------------------------------------------------------------
    // Static helpers
    // -------------------------------------------------------------------------

    /**
     * Test get_for_skill() returns all courses that have the skill assigned.
     */
    public function test_get_for_skill_returns_all_courses_with_skill(): void {
        $skillid = $this->create_skill();
        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();
        $this->assign_skill_to_course($c1->id, $skillid);
        $this->assign_skill_to_course($c2->id, $skillid);
        $this->assign_skill_to_course($c3->id, $skillid);

        $result = courseskills::get_for_skill($skillid);
        $this->assertCount(3, $result);
    }

    /**
     * Test remove_skills() removes a skill from all courses.
     */
    public function test_remove_skills_removes_from_all_courses(): void {
        global $DB;
        $skillid = $this->create_skill();
        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $this->assign_skill_to_course($c1->id, $skillid);
        $this->assign_skill_to_course($c2->id, $skillid);

        courseskills::remove_skills($skillid);

        $this->assertEquals(0, $DB->count_records('tool_skills_courses', ['skill' => $skillid]));
    }

    // -------------------------------------------------------------------------
    // get_points_earned_fromcourse
    // -------------------------------------------------------------------------

    /**
     * Test get_points_earned_fromcourse() returns the configured points for COMPLETIONPOINTS.
     */
    public function test_get_points_earned_fromcourse_returns_configured_points(): void {
        $course  = $this->getDataGenerator()->create_course();
        $skillid = $this->create_skill();
        $instanceid = $this->assign_skill_to_course($course->id, $skillid, 1, skills::COMPLETIONPOINTS, 75);

        $cs = courseskills::get($course->id);
        $cs->set_skill_instance($instanceid);
        $result = $cs->get_points_earned_fromcourse();

        $this->assertEquals(75, $result);
    }
}
