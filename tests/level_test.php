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
 * Tool Skills - PHPUnit tests for the level class.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for \tool_skills\level.
 *
 * @covers \tool_skills\level
 */
final class level_test extends \advanced_testcase {

    /** @var int Reusable skill id. */
    private int $skillid;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->skillid = $this->create_skill();
    }

    private function create_skill(): int {
        global $DB;
        static $counter = 0;
        $counter++;
        return $DB->insert_record('tool_skills', (object)[
            'name'         => 'Skill ' . $counter,
            'identitykey'  => 'sk' . $counter,
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

    private function create_level(int $skillid, int $points = 100): int {
        global $DB;
        return $DB->insert_record('tool_skills_levels', (object)[
            'skill'        => $skillid,
            'name'         => 'Level ' . $points,
            'points'       => $points,
            'status'       => 1,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Test level::get() returns a level instance with the correct points value.
     */
    public function test_get_returns_level_instance(): void {
        $levelid = $this->create_level($this->skillid, 75);
        $level = level::get($levelid);
        $this->assertInstanceOf(level::class, $level);
        $this->assertEquals(75, $level->get_points());
    }

    /**
     * Test delete_level() removes the record from the database.
     */
    public function test_delete_level_removes_from_db(): void {
        global $DB;
        $levelid = $this->create_level($this->skillid);
        level::get($levelid)->delete_level();
        $this->assertFalse($DB->record_exists('tool_skills_levels', ['id' => $levelid]));
    }

    /**
     * Test manage_level_instance() creates new level records for a skill.
     */
    public function test_manage_level_instance_creates_new_levels(): void {
        global $DB;
        $skill = skills::get($this->skillid);
        $levels = [
            1 => ['name' => 'Bronze', 'points' => 50,  'status' => 1, 'color' => ''],
            2 => ['name' => 'Silver', 'points' => 100, 'status' => 1, 'color' => ''],
        ];
        level::manage_level_instance($skill, $levels);
        $this->assertEquals(2, $DB->count_records('tool_skills_levels', ['skill' => $this->skillid]));
    }

    /**
     * Test manage_level_instance() updates an existing level without creating a duplicate.
     */
    public function test_manage_level_instance_updates_existing_levels(): void {
        global $DB;
        $skill = skills::get($this->skillid);
        $levelid = $this->create_level($this->skillid, 50);

        $levels = [
            1 => ['id' => $levelid, 'name' => 'Updated', 'points' => 60, 'status' => 1, 'color' => ''],
        ];
        level::manage_level_instance($skill, $levels);

        $this->assertEquals(1, $DB->count_records('tool_skills_levels', ['skill' => $this->skillid]));
        $this->assertEquals(60, $DB->get_field('tool_skills_levels', 'points', ['id' => $levelid]));
    }

    /**
     * Test manage_level_instance() returns false when given an empty levels array.
     */
    public function test_manage_level_instance_returns_false_for_empty_levels(): void {
        $skill = skills::get($this->skillid);
        $result = level::manage_level_instance($skill, []);
        $this->assertFalse($result);
    }

    /**
     * Test remove_skill_levels() deletes all levels belonging to a skill.
     */
    public function test_remove_skill_levels_deletes_all_levels(): void {
        global $DB;
        $this->create_level($this->skillid, 30);
        $this->create_level($this->skillid, 60);
        $this->create_level($this->skillid, 90);

        level::remove_skill_levels($this->skillid);

        $this->assertEquals(0, $DB->count_records('tool_skills_levels', ['skill' => $this->skillid]));
    }
}
