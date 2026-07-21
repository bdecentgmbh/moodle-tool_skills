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
 * Tool Skills - PHPUnit tests for the logs class.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Unit tests for \tool_skills\logs.
 *
 * @covers \tool_skills\logs
 */
final class logs_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Insert a raw award log record and return its id.
     *
     * @param int $skillid
     * @param int $userid
     * @param int $points
     * @param int $methodid
     * @param string $method
     * @return int
     */
    private function insert_log(int $skillid, int $userid, int $points, int $methodid, string $method = 'course'): int {
        global $DB;
        return $DB->insert_record('tool_skills_awardlogs', (object)[
            'skill'       => $skillid,
            'userid'      => $userid,
            'points'      => $points,
            'methodid'    => $methodid,
            'method'      => $method,
            'timecreated' => time(),
        ]);
    }

    /**
     * Test add() creates a new log entry in tool_skills_awardlogs.
     */
    public function test_add_creates_log_entry(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        logs::get()->add(1, $user->id, 50, 10, 'course');
        $this->assertTrue($DB->record_exists('tool_skills_awardlogs', [
            'skill' => 1, 'userid' => $user->id, 'methodid' => 10, 'method' => 'course',
        ]));
    }

    /**
     * Test add() updates an existing log entry instead of creating a duplicate.
     */
    public function test_add_updates_existing_log_entry(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        logs::get()->add(1, $user->id, 50, 10, 'course');
        logs::get()->add(1, $user->id, 80, 10, 'course');

        $this->assertEquals(1, $DB->count_records('tool_skills_awardlogs', [
            'skill' => 1, 'userid' => $user->id, 'methodid' => 10, 'method' => 'course',
        ]));
        $points = $DB->get_field('tool_skills_awardlogs', 'points', [
            'skill' => 1, 'userid' => $user->id, 'methodid' => 10, 'method' => 'course',
        ]);
        $this->assertEquals(80, $points);
    }

    /**
     * Test get_log() returns the matching stdClass record.
     */
    public function test_get_log_returns_existing_record(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->insert_log(2, $user->id, 40, 5, 'course');

        $log = logs::get()->get_log(2, $user->id, 5, 'course');
        $this->assertIsObject($log);
        $this->assertEquals(40, $log->points);
    }

    /**
     * Test get_log() returns false when no matching record exists.
     */
    public function test_get_log_returns_false_when_not_found(): void {
        $user = $this->getDataGenerator()->create_user();
        $result = logs::get()->get_log(999, $user->id, 999, 'course');
        $this->assertFalse($result);
    }

    /**
     * Test delete_user_log() removes only the target user's log entries.
     */
    public function test_delete_user_log_removes_only_target_user(): void {
        global $DB;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->insert_log(1, $user1->id, 50, 1);
        $this->insert_log(1, $user2->id, 50, 1);

        logs::get()->delete_user_log($user1->id);

        $this->assertFalse($DB->record_exists('tool_skills_awardlogs', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_skills_awardlogs', ['userid' => $user2->id]));
    }

    /**
     * Test delete_method_log() removes only logs for the specified method/methodid.
     */
    public function test_delete_method_log_removes_course_logs(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->insert_log(1, $user->id, 50, 10, 'course');
        $this->insert_log(1, $user->id, 30, 20, 'mod');

        logs::get()->delete_method_log(10, 'course');

        $this->assertFalse($DB->record_exists('tool_skills_awardlogs', ['methodid' => 10, 'method' => 'course']));
        $this->assertTrue($DB->record_exists('tool_skills_awardlogs', ['methodid' => 20, 'method' => 'mod']));
    }
}
