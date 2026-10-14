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
 * Tool skills - History of the users' skill points.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Records every change of a user's skill points in tool_skills_points_history and fires the events for it.
 *
 * The award logs keep only the current points per source; this history keeps every change with its time, so
 * points can be reported for any period. Earned points are the rows with the reasons award, revoke and import;
 * decay rows are what decay removed; all rows together are the net change.
 */
class points_history {
    /** @var string Points awarded by a source (course or activity completion, grade). */
    public const REASON_AWARD = 'award';

    /** @var string Points of a source taken back, for example when its points or its grade changed. */
    public const REASON_REVOKE = 'revoke';

    /** @var string Points removed by decay. */
    public const REASON_DECAY = 'decay';

    /** @var string Imported from the award logs when the history started, or a correction of the import. */
    public const REASON_IMPORT = 'import';

    /** @var string Any other change, for example by code that does not say why. */
    public const REASON_OTHER = 'other';

    /** @var string[] All reasons. */
    public const REASONS = [
        self::REASON_AWARD,
        self::REASON_REVOKE,
        self::REASON_DECAY,
        self::REASON_IMPORT,
        self::REASON_OTHER,
    ];

    /** @var string Database table of the history. */
    public const TABLE = 'tool_skills_points_history';

    /**
     * Record a change of a user's points and fire skill_points_changed (and skill_level_reached when the level rose).
     *
     * Nothing is recorded when the points did not change.
     *
     * @param skills $skill The skill.
     * @param int $userid The user.
     * @param int $oldpoints Points before the change.
     * @param int $newpoints Points after the change.
     * @param points_change|null $change Why and through which source; null records the reason "other".
     * @return int|null ID of the history row, null when nothing changed.
     */
    public static function record(skills $skill, int $userid, int $oldpoints, int $newpoints, ?points_change $change = null): ?int {
        global $DB;

        if ($oldpoints === $newpoints) {
            return null;
        }
        $change ??= new points_change(self::REASON_OTHER);

        $oldlevel = $skill->get_level_for_points($oldpoints);
        $newlevel = $skill->get_level_for_points($newpoints);
        $record = (object) [
            'skill' => $skill->get_id(),
            'userid' => $userid,
            'points' => $newpoints - $oldpoints,
            'oldpoints' => $oldpoints,
            'newpoints' => $newpoints,
            'oldlevel' => $oldlevel,
            'newlevel' => $newlevel,
            'reason' => $change->reason,
            'method' => $change->method,
            'methodid' => $change->methodid,
            'courseid' => $change->courseid,
            'cmid' => $change->cmid,
            'sourcename' => $change->sourcename !== null ? \core_text::substr($change->sourcename, 0, 1333) : null,
            'usermodified' => $change->usermodified,
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);

        \tool_skills\event\skill_points_changed::create_from_record($record)->trigger();
        if ($newlevel !== null && $newlevel !== $oldlevel && $newpoints > $oldpoints) {
            \tool_skills\event\skill_level_reached::create_from_record($record)->trigger();
        }
        return $record->id;
    }

    /**
     * The context of a history row: the course of its source while the course exists, otherwise the system.
     *
     * @param int|null $courseid Course of the source.
     * @return \context
     */
    public static function get_context(?int $courseid): \context {
        if ($courseid && ($context = \context_course::instance($courseid, IGNORE_MISSING))) {
            return $context;
        }
        return \context_system::instance();
    }

    /**
     * Delete the history of a user, for example when the user is deleted.
     *
     * @param int $userid
     * @return void
     */
    public static function delete_user_history(int $userid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }

    /**
     * Delete the history of a skill when the skill is deleted.
     *
     * @param int $skillid
     * @return void
     */
    public static function delete_skill_history(int $skillid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['skill' => $skillid]);
    }
}
