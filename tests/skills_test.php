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
 * Tool Skills - PHPUnit tests for the skills class.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Unit tests for \tool_skills\skills.
 *
 * @covers \tool_skills\skills
 */
final class skills_test extends \advanced_testcase {
    /**
     * Insert a minimal skill record and return its id.
     *
     * @param array $overrides Optional field overrides.
     * @return int The new skill id.
     */
    private function create_skill(array $overrides = []): int {
        global $DB;
        static $counter = 0;
        $counter++;
        $record = (object) array_merge([
            'name'         => 'Test Skill ' . $counter,
            'identitykey'  => 'testskill' . $counter,
            'description'  => '',
            'status'       => skills::STATUS_ENABLE,
            'categories'   => '[]',
            'learningtime' => '',
            'levelscount'  => 0,
            'archived'     => 0,
            'timearchived' => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ], $overrides);
        return $DB->insert_record('tool_skills', $record);
    }

    /**
     * Insert a level for the given skill and return its id.
     *
     * @param int $skillid
     * @param int $points
     * @return int
     */
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
     * Insert a courseskills record and return its id.
     *
     * @param int $courseid
     * @param int $skillid
     * @param array $overrides
     * @return int
     */
    private function create_courseskill(int $courseid, int $skillid, array $overrides = []): int {
        global $DB;
        $record = (object) array_merge([
            'courseid'      => $courseid,
            'skill'         => $skillid,
            'status'        => 1,
            'uponcompletion' => skills::COMPLETIONPOINTS,
            'points'        => 50,
            'level'         => 0,
            'timemodified'  => time(),
        ], $overrides);
        return $DB->insert_record('tool_skills_courses', $record);
    }

    /**
     * Test that skills::get() returns a skills instance with the correct id.
     */
    public function test_get_returns_skills_instance(): void {
        $this->resetAfterTest(true);
        $skillid = $this->create_skill();
        $skill = skills::get($skillid);
        $this->assertInstanceOf(skills::class, $skill);
        $this->assertEquals($skillid, $skill->get_id());
    }

    /**
     * Test that get_name() returns the stored name.
     */
    public function test_get_name_returns_formatted_name(): void {
        $this->resetAfterTest(true);
        $skillid = $this->create_skill(['name' => 'My Skill']);
        $skill = skills::get($skillid);
        $this->assertEquals('My Skill', $skill->get_name());
    }

    /**
     * Test disabling a skill sets status to 0 in the DB.
     */
    public function test_update_status_disable(): void {
        global $DB;
        $this->resetAfterTest(true);
        $skillid = $this->create_skill(['status' => skills::STATUS_ENABLE]);
        $skill = skills::get($skillid);
        $skill->update_status(false);
        $this->assertEquals(0, $DB->get_field('tool_skills', 'status', ['id' => $skillid]));
    }

    /**
     * Test enabling a skill sets status to 1 in the DB.
     */
    public function test_update_status_enable(): void {
        global $DB;
        $this->resetAfterTest(true);
        $skillid = $this->create_skill(['status' => skills::STATUS_DISABLE]);
        $skill = skills::get($skillid);
        $skill->update_status(true);
        $this->assertEquals(1, $DB->get_field('tool_skills', 'status', ['id' => $skillid]));
    }

    /**
     * Test that update_field() persists a changed value to the DB.
     */
    public function test_update_field_persists_to_db(): void {
        global $DB;
        $this->resetAfterTest(true);
        $skillid = $this->create_skill(['name' => 'Old Name']);
        $skill = skills::get($skillid);
        $skill->update_field('name', 'New Name');
        $this->assertEquals('New Name', $DB->get_field('tool_skills', 'name', ['id' => $skillid]));
    }

    /**
     * Test that delete_skill() removes the skill and all its associated records.
     */
    public function test_delete_skill_removes_skill_and_related_records(): void {
        global $DB;
        $this->resetAfterTest(true);
        $skillid = $this->create_skill();
        $levelid = $this->create_level($skillid, 100);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->create_courseskill($course->id, $skillid);

        // Insert userpoints and awardlog records.
        $DB->insert_record('tool_skills_userpoints', (object)[
            'skill' => $skillid, 'userid' => $user->id, 'points' => 50,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('tool_skills_awardlogs', (object)[
            'skill' => $skillid, 'userid' => $user->id, 'points' => 50,
            'methodid' => $course->id, 'method' => 'course', 'timecreated' => time(),
        ]);

        skills::get($skillid)->delete_skill();

        $this->assertFalse($DB->record_exists('tool_skills', ['id' => $skillid]));
        $this->assertFalse($DB->record_exists('tool_skills_levels', ['skill' => $skillid]));
        $this->assertFalse($DB->record_exists('tool_skills_courses', ['skill' => $skillid]));
        $this->assertFalse($DB->record_exists('tool_skills_userpoints', ['skill' => $skillid]));
        $this->assertFalse($DB->record_exists('tool_skills_awardlogs', ['skill' => $skillid]));
    }

    /**
     * Test archiving a skill sets archived = 1 and records a timestamp.
     */
    public function test_archive_skill(): void {
        global $DB;
        $this->resetAfterTest(true);
        $skillid = $this->create_skill();
        skills::get($skillid)->archive_skill();
        $record = $DB->get_record('tool_skills', ['id' => $skillid]);
        $this->assertEquals(1, $record->archived);
        $this->assertGreaterThan(0, $record->timearchived);
    }

    /**
     * Test activating an archived skill sets archived = 0.
     */
    public function test_active_skill(): void {
        global $DB;
        $this->resetAfterTest(true);
        $skillid = $this->create_skill(['archived' => 1, 'timearchived' => time()]);
        skills::get($skillid)->active_skill();
        $this->assertEquals(0, $DB->get_field('tool_skills', 'archived', ['id' => $skillid]));
    }

    /**
     * Test get_points_to_earnskill() returns the highest level's points.
     */
    public function test_get_points_to_earnskill_returns_max_level_points(): void {
        $this->resetAfterTest(true);
        $skillid = $this->create_skill();
        $this->create_level($skillid, 50);
        $this->create_level($skillid, 100);
        $skill = skills::get($skillid);
        $this->assertEquals(100, $skill->get_points_to_earnskill());
    }

    /**
     * Test get_points_to_earnskill() returns 0 when a skill has no levels.
     */
    public function test_get_points_to_earnskill_returns_zero_when_no_levels(): void {
        $this->resetAfterTest(true);
        $skillid = $this->create_skill();
        $skill = skills::get($skillid);
        $this->assertEquals(0, $skill->get_points_to_earnskill());
    }

    /**
     * Helper to build a minimal allocation_method stub for point operations.
     * Returns a courseskills instance with the skill instance set.
     */
    private function make_skillobj(int $courseid, int $skillid): courseskills {
        $courseskillid = $this->create_courseskill($courseid, $skillid);
        $skillobj = courseskills::get($courseid);
        $skillobj->set_skill_instance($courseskillid);
        return $skillobj;
    }

    /**
     * Test increase_points() creates a userpoints record with the correct points.
     */
    public function test_increase_points_awards_points_to_user(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $skillid = $this->create_skill();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $skillobj = $this->make_skillobj($course->id, $skillid);

        skills::get($skillid)->increase_points($skillobj, 50, $user->id);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(50, $points);
    }

    /**
     * Test that increase_points() accumulates across multiple calls.
     */
    public function test_increase_points_accumulates_on_subsequent_calls(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $skillid = $this->create_skill();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $skillobj = $this->make_skillobj($course->id, $skillid);

        $skill = skills::get($skillid);
        $skill->increase_points($skillobj, 30, $user->id);
        $skill->increase_points($skillobj, 20, $user->id);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(50, $points);
    }

    /**
     * Test set_userskill_points() overwrites any existing points.
     */
    public function test_set_userskill_points_overwrites_existing(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $skillid = $this->create_skill();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $skillobj = $this->make_skillobj($course->id, $skillid);

        $skill = skills::get($skillid);
        $skill->increase_points($skillobj, 100, $user->id);
        $skill->set_userskill_points($user->id, 40);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(40, $points);
    }

    /**
     * Test force_level() sets points to exactly the level's threshold, even when user had more.
     */
    public function test_force_level_sets_exact_level_points(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $skillid = $this->create_skill();
        $levelid = $this->create_level($skillid, 80);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $skillobj = $this->make_skillobj($course->id, $skillid);

        $skill = skills::get($skillid);
        $skill->increase_points($skillobj, 100, $user->id);
        $skill->force_level($skillobj, $levelid, $user->id);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(80, $points);
    }

    /**
     * Test moveto_level() does not reduce points if user is already above the level.
     */
    public function test_moveto_level_does_not_reduce_points(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $skillid = $this->create_skill();
        $levelid = $this->create_level($skillid, 80);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $skillobj = $this->make_skillobj($course->id, $skillid);

        $skill = skills::get($skillid);
        $skill->increase_points($skillobj, 100, $user->id);
        $skill->moveto_level($skillobj, $levelid, $user->id);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(100, $points);
    }

    /**
     * Test moveto_level() raises points up to the level threshold when user is below it.
     */
    public function test_moveto_level_upgrades_when_below(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $skillid = $this->create_skill();
        $levelid = $this->create_level($skillid, 80);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $skillobj = $this->make_skillobj($course->id, $skillid);

        $skill = skills::get($skillid);
        $skill->increase_points($skillobj, 30, $user->id);
        $skill->moveto_level($skillobj, $levelid, $user->id);

        $points = $DB->get_field('tool_skills_userpoints', 'points', ['skill' => $skillid, 'userid' => $user->id]);
        $this->assertEquals(80, $points);
    }

    /**
     * Test manage_instance() creates a skill and associated levels from form data.
     */
    public function test_manage_instance_creates_skill_with_levels(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $formdata = (object)[
            'name'         => 'Managed Skill',
            'identitykey'  => 'managedskill1',
            'description'  => '',
            'status'       => skills::STATUS_ENABLE,
            'categories'   => [],
            'learningtime' => '',
            'levelscount'  => 1,
            'levels'       => [
                1 => ['name' => 'Level One', 'points' => 100, 'status' => 1, 'color' => ''],
            ],
        ];

        $skillid = skills::manage_instance($formdata);
        $this->assertNotEmpty($skillid);
        $this->assertTrue($DB->record_exists('tool_skills', ['id' => $skillid, 'name' => 'Managed Skill']));
        $this->assertEquals(1, $DB->count_records('tool_skills_levels', ['skill' => $skillid]));
    }

    /**
     * Test manage_instance() updates an existing skill without creating a duplicate.
     */
    public function test_manage_instance_updates_existing_skill(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $formdata = (object)[
            'name'         => 'Original Name',
            'identitykey'  => 'managedskill2',
            'description'  => '',
            'status'       => skills::STATUS_ENABLE,
            'categories'   => [],
            'learningtime' => '',
            'levelscount'  => 0,
        ];
        $skillid = skills::manage_instance($formdata);

        $formdata->id   = $skillid;
        $formdata->name = 'Updated Name';
        skills::manage_instance($formdata);

        $this->assertEquals(1, $DB->count_records('tool_skills', ['identitykey' => 'managedskill2']));
        $this->assertEquals('Updated Name', $DB->get_field('tool_skills', 'name', ['id' => $skillid]));
    }
}
