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
 * Tool Skills - PHPUnit tests for the helper class.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Unit tests for \tool_skills\helper.
 *
 * @covers \tool_skills\helper
 */
final class helper_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Create the DB fixtures needed by most tests.
     */
    private function create_skill(int $points = 0): int {
        global $DB;
        static $n = 0;
        $n++;
        return $DB->insert_record('tool_skills', (object)[
            'name'         => 'Skill ' . $n,
            'identitykey'  => 'hsk' . $n,
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

    private function insert_userpoints(int $skillid, int $userid, int $points): void {
        global $DB;
        $DB->insert_record('tool_skills_userpoints', (object)[
            'skill'        => $skillid,
            'userid'       => $userid,
            'points'       => $points,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);
    }

    private function create_courseskill(int $courseid, int $skillid, int $levelpoints): int {
        global $DB;
        $levelid = $this->create_level($skillid, $levelpoints);
        return $DB->insert_record('tool_skills_courses', (object)[
            'courseid'       => $courseid,
            'skill'          => $skillid,
            'status'         => 1,
            'uponcompletion' => skills::COMPLETIONFORCELEVEL,
            'points'         => 0,
            'level'          => $levelid,
            'timemodified'   => time(),
        ]);
    }

    /**
     * Test get_skills_list() returns all skills as skills instances.
     */
    public function test_get_skills_list_returns_all_skills(): void {
        $this->create_skill();
        $this->create_skill();
        $this->create_skill();

        $list = helper::get_skills_list();
        $this->assertCount(3, $list);
        foreach ($list as $skill) {
            $this->assertInstanceOf(skills::class, $skill);
        }
    }

    /**
     * Test get_user_completedskills() returns skills the user has fully completed (100%).
     */
    public function test_get_user_completedskills_returns_completed_skills(): void {
        $user    = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->create_level($skillid, 100); // Max = 100 pts.
        $this->insert_userpoints($skillid, $user->id, 100);

        $result = helper::get_user_completedskills($user->id);
        $this->assertNotEmpty($result);
        $this->assertContains($skillid, $result);
    }

    /**
     * Test get_user_completedskills() excludes skills where the user has not reached 100%.
     */
    public function test_get_user_completedskills_excludes_incomplete(): void {
        $user    = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->create_level($skillid, 100);
        $this->insert_userpoints($skillid, $user->id, 50); // Only 50%.

        $result = helper::get_user_completedskills($user->id);
        $this->assertEmpty($result);
    }

    /**
     * Test get_courses_skill_points() sums max level points across all given courses.
     */
    public function test_get_courses_skill_points_sums_correctly(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $skill1  = $this->create_skill();
        $skill2  = $this->create_skill();

        $this->create_courseskill($course1->id, $skill1, 100);
        $this->create_courseskill($course2->id, $skill2, 50);

        $total = helper::get_courses_skill_points([$course1->id, $course2->id]);
        $this->assertEquals(150, $total);
    }
}
