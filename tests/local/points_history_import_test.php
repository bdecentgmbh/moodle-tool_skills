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
 * Tool skills - Tests of the import into the points history.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\local;

use tool_skills\points_history;
use tool_skills\skills;

#[\PHPUnit\Framework\Attributes\CoversClass(points_history_import::class)]
/**
 * Tests of the import into the points history.
 *
 * @covers \tool_skills\local\points_history_import
 * @covers \tool_skills\task\import_points_history
 */
final class points_history_import_test extends \advanced_testcase {
    /** @var int */
    private int $skillid;

    /** @var int Level reached with 10 points. */
    private int $bronze;

    /** @var \stdClass */
    private \stdClass $course;

    /** @var int tool_skills_courses id. */
    private int $courseskillid;

    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        points_history_import::reset_caches();

        $this->skillid = (int) $DB->insert_record('tool_skills', (object) [
            'name' => 'Safety', 'identitykey' => 'safety', 'description' => '', 'status' => 1, 'categories' => '[]',
            'learningtime' => '', 'levelscount' => 1, 'archived' => 0, 'timearchived' => 0,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $this->bronze = (int) $DB->insert_record('tool_skills_levels', (object) [
            'skill' => $this->skillid, 'name' => 'Bronze', 'points' => 10, 'status' => 1,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $this->course = $this->getDataGenerator()->create_course(['fullname' => 'Safety basics']);
        $this->courseskillid = (int) $DB->insert_record('tool_skills_courses', (object) [
            'courseid' => $this->course->id, 'skill' => $this->skillid, 'status' => 1, 'uponcompletion' => 1,
            'points' => 8, 'level' => 0, 'timemodified' => time(),
        ]);
    }

    /**
     * Store a user's current points and an award log row.
     *
     * @param int $userid
     * @param int $current Current points of the user.
     * @param array $logs Award logs as [points, time, method, methodid].
     * @return void
     */
    private function legacy(int $userid, int $current, array $logs): void {
        global $DB;
        $DB->insert_record('tool_skills_userpoints', (object) [
            'skill' => $this->skillid, 'userid' => $userid, 'points' => $current, 'timecreated' => time(),
        ]);
        foreach ($logs as [$points, $time, $method, $methodid]) {
            $DB->insert_record('tool_skills_awardlogs', (object) [
                'skill' => $this->skillid, 'userid' => $userid, 'points' => $points, 'methodid' => $methodid,
                'method' => $method, 'timecreated' => $time,
            ]);
        }
    }

    public function test_award_logs_are_replayed_and_corrected_to_the_current_points(): void {
        global $DB;
        $start = time();
        $user = $this->getDataGenerator()->create_user();
        // 8 from the course a year ago, 4 from something unknown; the user has 15 now (e.g. an old forced level).
        $this->legacy($user->id, 15, [[8, $start - YEARSECS, 'course', $this->courseskillid],
            [4, $start - DAYSECS, 'unknown', 99], [0, $start - HOURSECS, 'course', $this->courseskillid + 1]]);

        $this->assertEquals(3, points_history_import::import_skill($this->skillid, $start));

        $rows = array_values($DB->get_records(points_history::TABLE, ['userid' => $user->id], 'timecreated ASC, id ASC'));
        $this->assertCount(3, $rows);
        $this->assertEquals([8, 4, 3], array_map('intval', array_column($rows, 'points')));
        $this->assertEquals([8, 12, 15], array_map('intval', array_column($rows, 'newpoints')));
        $this->assertEquals(['import', 'import', 'import'], array_column($rows, 'reason'));
        // The course row has its source, the unknown method none.
        $this->assertEquals(
            [$this->course->id, 'Safety basics', $start - YEARSECS],
            [$rows[0]->courseid, $rows[0]->sourcename, $rows[0]->timecreated]
        );
        $this->assertNull($rows[1]->courseid);
        // The level is reached on the second row.
        $this->assertEquals([null, $this->bronze], [$rows[1]->oldlevel, $rows[1]->newlevel]);
        // The correction is dated when the history started.
        $this->assertTrue(points_history_import::is_correction($rows[2]));
        $this->assertFalse(points_history_import::is_correction($rows[0]));
        $this->assertEquals($start, $rows[2]->timecreated);
        // Done once only.
        $this->assertEquals(0, points_history_import::import_skill($this->skillid, $start));
    }

    public function test_changes_recorded_after_the_start_are_not_imported_again(): void {
        global $DB;
        $start = time() - 10;
        $user = $this->getDataGenerator()->create_user();
        // Before the start the user had 8 points from the course; after it, 5 more were recorded live.
        $this->legacy($user->id, 8, [[8, $start - DAYSECS, 'course', $this->courseskillid]]);
        skills::get($this->skillid)->set_userskill_points($user->id, 13);
        // An award log touched after the start belongs to the live history.
        $DB->insert_record('tool_skills_awardlogs', (object) [
            'skill' => $this->skillid, 'userid' => $user->id, 'points' => 5, 'methodid' => 7, 'method' => 'course',
            'timecreated' => $start + 5,
        ]);

        $this->assertEquals(1, points_history_import::import_skill($this->skillid, $start));

        $rows = array_values($DB->get_records(points_history::TABLE, ['userid' => $user->id], 'timecreated ASC, id ASC'));
        $this->assertCount(2, $rows);
        $this->assertEquals(['import', 'other'], array_column($rows, 'reason'));
        $this->assertEquals(13, array_sum(array_column($rows, 'points')));
    }

    public function test_users_with_points_but_no_logs_get_a_correction_only(): void {
        global $DB;
        $start = time();
        $user = $this->getDataGenerator()->create_user();
        $this->legacy($user->id, 6, []);

        $this->assertEquals(1, points_history_import::import_skill($this->skillid, $start));
        $row = $DB->get_record(points_history::TABLE, ['userid' => $user->id]);
        $this->assertEquals([6, 0, 6], [(int) $row->points, (int) $row->oldpoints, (int) $row->newpoints]);
        $this->assertTrue(points_history_import::is_correction($row));
    }

    public function test_the_task_imports_every_skill_and_fires_no_events(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->legacy($user->id, 8, [[8, time() - DAYSECS, 'course', $this->courseskillid]]);

        // Without a start, nothing happens.
        (new \tool_skills\task\import_points_history())->execute();
        $this->assertEquals(0, $DB->count_records(points_history::TABLE));

        set_config('historystart', time(), 'tool_skills');
        $sink = $this->redirectEvents();
        $this->expectOutputRegex('/Imported 1 rows/');
        (new \tool_skills\task\import_points_history())->execute();
        $this->assertCount(0, $sink->get_events());
        $sink->close();
        $this->assertEquals(1, $DB->count_records(points_history::TABLE));
    }
}
