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
 * Tool skills - Import of the points earned before the points history started.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\local;

use stdClass;
use tool_skills\helper;
use tool_skills\points_history;
use tool_skills\skills;

/**
 * Fills the points history with what is known from before it started.
 *
 * The award logs keep the latest award per source, dated when it was given; addons add their own logs (decay adds
 * its decay log) through a get_points_history_import($skillid, $historystart) method of their manage_skills class.
 * Each user's rows are replayed by date to work out the points and levels before and after. Where the replayed
 * total differs from the user's points when the history started (for example because "set level" logged
 * the level's points, or because earlier awards of a source were overwritten), one correction row is added,
 * dated when the history started. Imported rows fire no events.
 */
class points_history_import {
    /** @var array Cache of resolved sources, keyed by "method:methodid". */
    protected static array $sources = [];

    /**
     * Import the history of every skill.
     *
     * @param int $historystart When the history started recording; only earlier data is imported.
     * @return int Number of rows imported.
     */
    public static function import_all(int $historystart): int {
        global $DB;

        $count = 0;
        foreach ($DB->get_fieldset_select('tool_skills', 'id', '1 = 1') as $skillid) {
            $count += self::import_skill((int) $skillid, $historystart);
        }
        return $count;
    }

    /**
     * Import the history of one skill. A skill that already has imported rows is left alone.
     *
     * @param int $skillid
     * @param int $historystart When the history started recording; only earlier data is imported.
     * @return int Number of rows imported.
     */
    public static function import_skill(int $skillid, int $historystart): int {
        global $DB;

        if ($DB->record_exists(points_history::TABLE, ['skill' => $skillid, 'reason' => points_history::REASON_IMPORT])) {
            return 0;
        }
        $skill = skills::get($skillid);

        // Rows to import, per user.
        $rows = [];
        $logs = $DB->get_recordset_select(
            'tool_skills_awardlogs',
            'skill = :skill AND points <> 0 AND timecreated < :historystart',
            ['skill' => $skillid, 'historystart' => $historystart],
            'timecreated ASC, id ASC'
        );
        foreach ($logs as $log) {
            [$courseid, $cmid, $name] = self::get_source((string) $log->method, (int) $log->methodid);
            $rows[(int) $log->userid][] = (object) [
                'points' => (int) $log->points,
                'method' => $log->method,
                'methodid' => (int) $log->methodid,
                'courseid' => $courseid,
                'cmid' => $cmid,
                'sourcename' => $name,
                'usermodified' => null,
                'timecreated' => (int) $log->timecreated,
            ];
        }
        $logs->close();

        foreach (helper::get_addon_extend_method('get_points_history_import') as $addon) {
            foreach ($addon->get_points_history_import($skillid, $historystart) as $row) {
                $rows[(int) $row->userid][] = (object) [
                    'points' => (int) $row->points,
                    'method' => $row->method ?? null,
                    'methodid' => isset($row->methodid) ? (int) $row->methodid : null,
                    'courseid' => isset($row->courseid) ? (int) $row->courseid : null,
                    'cmid' => isset($row->cmid) ? (int) $row->cmid : null,
                    'sourcename' => $row->sourcename ?? null,
                    'usermodified' => isset($row->usermodified) ? (int) $row->usermodified : null,
                    'timecreated' => (int) $row->timecreated,
                ];
            }
        }

        // Each user's points when the history started: before their first recorded change, else their current points.
        $startpoints = $DB->get_records_menu('tool_skills_userpoints', ['skill' => $skillid], '', 'userid, points');
        $sql = "SELECT h.userid, h.oldpoints
                  FROM {" . points_history::TABLE . "} h
                  JOIN (SELECT userid, MIN(id) AS firstid
                          FROM {" . points_history::TABLE . "}
                         WHERE skill = :skill AND reason <> :import
                      GROUP BY userid) f ON f.firstid = h.id";
        $recorded = $DB->get_records_sql_menu($sql, ['skill' => $skillid, 'import' => points_history::REASON_IMPORT]);
        foreach ($recorded as $userid => $oldpoints) {
            $startpoints[$userid] = $oldpoints;
        }

        $count = 0;
        $userids = array_unique(array_merge(array_keys($rows), array_keys($startpoints)));
        foreach ($userids as $userid) {
            $userrows = $rows[$userid] ?? [];
            usort($userrows, fn($a, $b) => $a->timecreated <=> $b->timecreated);
            $balance = 0;
            foreach ($userrows as $row) {
                $count += self::insert($skill, (int) $userid, $row, $balance);
            }
            $target = (int) ($startpoints[$userid] ?? 0);
            if ($target !== $balance) {
                // Correction: the difference between what was imported and the points the user had.
                $correction = (object) [
                    'points' => $target - $balance,
                    'method' => null,
                    'methodid' => null,
                    'courseid' => null,
                    'cmid' => null,
                    'sourcename' => null,
                    'usermodified' => null,
                    'timecreated' => $historystart,
                ];
                $count += self::insert($skill, (int) $userid, $correction, $balance);
            }
        }
        return $count;
    }

    /**
     * Whether a history row is the correction of the import.
     *
     * @param stdClass $row A tool_skills_points_history record.
     * @return bool
     */
    public static function is_correction(stdClass $row): bool {
        return $row->reason === points_history::REASON_IMPORT && empty($row->method);
    }

    /**
     * Insert an imported row and move the running balance on.
     *
     * @param skills $skill
     * @param int $userid
     * @param stdClass $row Row data: points, method, methodid, courseid, cmid, sourcename, usermodified, timecreated.
     * @param int $balance Running balance, updated.
     * @return int 1 when a row was inserted, 0 for a change of 0 points.
     */
    protected static function insert(skills $skill, int $userid, stdClass $row, int &$balance): int {
        global $DB;

        if ($row->points === 0) {
            return 0;
        }
        $oldpoints = $balance;
        $balance += $row->points;
        $DB->insert_record(points_history::TABLE, (object) [
            'skill' => $skill->get_id(),
            'userid' => $userid,
            'points' => $row->points,
            'oldpoints' => $oldpoints,
            'newpoints' => $balance,
            'oldlevel' => $skill->get_level_for_points($oldpoints),
            'newlevel' => $skill->get_level_for_points($balance),
            'reason' => points_history::REASON_IMPORT,
            'method' => $row->method,
            'methodid' => $row->methodid,
            'courseid' => $row->courseid,
            'cmid' => $row->cmid,
            'sourcename' => $row->sourcename !== null ? \core_text::substr($row->sourcename, 0, 1333) : null,
            'usermodified' => $row->usermodified,
            'timecreated' => $row->timecreated,
        ]);
        return 1;
    }

    /**
     * Course, course module and name of an award log's source. Courses are resolved here, other methods by the
     * addon that owns them (get_points_history_source($method, $methodid) of its manage_skills class).
     *
     * @param string $method
     * @param int $methodid
     * @return array [courseid|null, cmid|null, name|null]
     */
    protected static function get_source(string $method, int $methodid): array {
        global $DB;

        $key = $method . ':' . $methodid;
        if (array_key_exists($key, self::$sources)) {
            return self::$sources[$key];
        }
        $source = [null, null, null];
        if ($method === 'course') {
            $courseid = $DB->get_field('tool_skills_courses', 'courseid', ['id' => $methodid]);
            if ($courseid) {
                $name = $DB->get_field('course', 'fullname', ['id' => $courseid]);
                $source = [(int) $courseid, null, $name === false ? null : $name];
            }
        } else {
            foreach (helper::get_addon_extend_method('get_points_history_source') as $addon) {
                if ($found = $addon->get_points_history_source($method, $methodid)) {
                    $source = $found;
                    break;
                }
            }
        }
        return self::$sources[$key] = $source;
    }

    /**
     * Forget the cached sources (for tests).
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$sources = [];
    }
}
