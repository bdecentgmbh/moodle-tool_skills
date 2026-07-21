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
 * Tool Skills - PHPUnit tests for the user class.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Unit tests for \tool_skills\user.
 *
 * @covers \tool_skills\user
 */
final class user_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Insert a minimal skill record and return its id.
     *
     * @param string $key Identity key prefix.
     * @return int
     */
    private function create_skill(string $key = 'sk1'): int {
        global $DB;
        static $n = 0;
        $n++;
        return $DB->insert_record('tool_skills', (object)[
            'name'         => 'Skill ' . $n,
            'identitykey'  => $key . $n,
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
     * Insert a level for the given skill and return its id.
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
     * Insert a raw userpoints record.
     *
     * @param int $skillid
     * @param int $userid
     * @param int $points
     * @return void
     */
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

    /**
     * Insert a raw award log record.
     *
     * @param int $skillid
     * @param int $userid
     * @param int $points
     * @param int $methodid
     * @param string $method
     * @return void
     */
    private function insert_awardlog(int $skillid, int $userid, int $points, int $methodid, string $method = 'course'): void {
        global $DB;
        $DB->insert_record('tool_skills_awardlogs', (object)[
            'skill'       => $skillid,
            'userid'      => $userid,
            'points'      => $points,
            'methodid'    => $methodid,
            'method'      => $method,
            'timecreated' => time(),
        ]);
    }

    /**
     * Test user::get() returns the same object instance on repeated calls (singleton).
     */
    public function test_get_returns_same_instance_singleton(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $instance1 = user::get($moodleuser->id);
        $instance2 = user::get($moodleuser->id);
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test get_user_points() returns an array with the user's point records.
     */
    public function test_get_user_points_returns_array(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->insert_userpoints($skillid, $moodleuser->id, 60);

        $result = user::get($moodleuser->id)->get_user_points();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test get_user_points(false) returns raw records without additional enrichment.
     */
    public function test_get_user_points_without_data_returns_records(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->insert_userpoints($skillid, $moodleuser->id, 60);

        $result = user::get($moodleuser->id)->get_user_points(false);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $first = reset($result);
        $this->assertEquals(60, $first->points);
    }

    /**
     * Test remove_user_skillpoints() deletes all point and log records for the user.
     */
    public function test_remove_user_skillpoints_deletes_all_records(): void {
        global $DB;
        $moodleuser = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->insert_userpoints($skillid, $moodleuser->id, 50);
        $this->insert_awardlog($skillid, $moodleuser->id, 50, 1);

        user::get($moodleuser->id)->remove_user_skillpoints();

        $this->assertFalse($DB->record_exists('tool_skills_userpoints', ['userid' => $moodleuser->id]));
        $this->assertFalse($DB->record_exists('tool_skills_awardlogs', ['userid' => $moodleuser->id]));
    }

    /**
     * Test get_user_proficency_level() returns the highest level the user has reached.
     */
    public function test_get_user_proficiency_level_returns_correct_level(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->create_level($skillid, 50);
        $this->create_level($skillid, 100);
        $this->insert_userpoints($skillid, $moodleuser->id, 75);

        $result = user::get($moodleuser->id)->get_user_proficency_level($skillid, 75);
        // Should return a non-empty string (level name) for a reached level.
        $this->assertNotEmpty($result);
    }

    /**
     * Test get_user_proficency_level() returns empty string when user is below first level.
     */
    public function test_get_user_proficiency_level_returns_empty_below_first_level(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->create_level($skillid, 50);
        $this->insert_userpoints($skillid, $moodleuser->id, 10);

        $result = user::get($moodleuser->id)->get_user_proficency_level($skillid, 10);
        $this->assertEmpty($result);
    }

    /**
     * Test get_user_percentage() calculates correctly.
     */
    public function test_get_user_percentage_calculates_correctly(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->create_level($skillid, 100); // Max points = 100.

        $result = user::get($moodleuser->id)->get_user_percentage($skillid, 50);
        $this->assertEquals(50, (int) $result);
    }

    /**
     * Test get_user_percentage() returns the raw ratio when user exceeds the max points.
     */
    public function test_get_user_percentage_exceeds_max(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $this->create_level($skillid, 100);

        $result = user::get($moodleuser->id)->get_user_percentage($skillid, 150);
        $this->assertEquals('150%', $result);
    }

    /**
     * Test get_user_award_by_method() returns the points from a matching log entry.
     */
    public function test_get_user_award_by_method_returns_points(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $skillid = $this->create_skill();
        $courseid = 42;
        $this->insert_awardlog($skillid, $moodleuser->id, 40, $courseid, 'course');

        $result = user::get($moodleuser->id)->get_user_award_by_method('course', $courseid);
        $this->assertEquals(40, $result);
    }

    /**
     * Test get_user_award_by_method() returns null when no matching log exists.
     */
    public function test_get_user_award_by_method_returns_null_when_none(): void {
        $moodleuser = $this->getDataGenerator()->create_user();
        $result = user::get($moodleuser->id)->get_user_award_by_method('course', 9999);
        $this->assertNull($result);
    }
}
