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

    /**
     * Insert a minimal skill level record and return its id.
     *
     * @param int $skillid
     * @param int $points
     * @return int
     */
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
     *
     * @param int $courseid
     * @param int $skillid
     * @param int $status
     * @param int $uponcompletion
     * @param int $points
     * @param int $level
     * @return int
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
     * Mark a course complete for a user at the data level.
     *
     * These are unit tests of manage_course_completions(), which is invoked directly below, so the
     * completion state is written straight to {course_completions} rather than through the completion
     * API. That keeps is_course_complete() returning true without firing the course_completed event
     * (whose observer would award the points a second time).
     *
     * @param \stdClass $course
     * @param \stdClass $user
     */
    private function complete_course(\stdClass $course, \stdClass $user): void {
        global $DB;

        $DB->insert_record('course_completions', (object)[
            'userid'        => $user->id,
            'course'        => $course->id,
            'timeenrolled'  => time(),
            'timestarted'   => time(),
            'timecompleted' => time(),
            'reaggregate'   => 0,
        ]);
    }

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
        $this->assign_skill_to_course($course->id, $skill3, 0); // Disabled.

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
        $this->complete_course($course, $user);
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
        $this->complete_course($course, $user);
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
        $this->complete_course($course, $user);
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

    /**
     * Test is_skill_available_for_course() honours category restriction, archived and disabled state.
     */
    public function test_is_skill_available_for_course(): void {
        global $DB;

        $cat = $this->getDataGenerator()->create_category();
        $othercat = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        // A global skill (no category restriction) is available.
        $global = $this->create_skill();
        $this->assertTrue(courseskills::is_skill_available_for_course($global, $course->id));

        // Restricted to the course's category: available.
        $inid = $DB->insert_record('tool_skills', (object)[
            'name' => 'InCat', 'identitykey' => 'incat', 'description' => '', 'status' => 1,
            'categories' => json_encode([(int) $cat->id]), 'learningtime' => '', 'levelscount' => 0,
            'archived' => 0, 'timearchived' => 0, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $this->assertTrue(courseskills::is_skill_available_for_course($inid, $course->id));

        // Restricted to a different category: not available.
        $outid = $DB->insert_record('tool_skills', (object)[
            'name' => 'OutCat', 'identitykey' => 'outcat', 'description' => '', 'status' => 1,
            'categories' => json_encode([(int) $othercat->id]), 'learningtime' => '', 'levelscount' => 0,
            'archived' => 0, 'timearchived' => 0, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $this->assertFalse(courseskills::is_skill_available_for_course($outid, $course->id));

        // Archived skill: not available.
        $DB->set_field('tool_skills', 'archived', 1, ['id' => $global]);
        $this->assertFalse(courseskills::is_skill_available_for_course($global, $course->id));
    }

    /**
     * Test level_belongs_to_skill() only accepts a level that belongs to the given skill.
     */
    public function test_level_belongs_to_skill(): void {
        $skill1 = $this->create_skill();
        $skill2 = $this->create_skill();
        $level = $this->create_level($skill1, 10);

        $this->assertTrue(courseskills::level_belongs_to_skill($level, $skill1));
        $this->assertFalse(courseskills::level_belongs_to_skill($level, $skill2));
    }
}
