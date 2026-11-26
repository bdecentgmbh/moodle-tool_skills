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
 * Tool skills - Event: a user's skill points changed.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\event;

use tool_skills\points_history;

/**
 * A user's points for a skill changed; fired for every row of the points history.
 *
 * The context is the course of the source while it exists, otherwise the system.
 *
 * @property-read array $other {
 *      - int skillid: the skill.
 *      - int points: the change (newpoints - oldpoints).
 *      - int oldpoints: points before the change.
 *      - int newpoints: points after the change.
 *      - string reason: award, revoke, decay, import or other.
 *      - string|null method: source type, for example course, activity or decay.
 *      - int|null methodid: ID of the source in the table of its method.
 * }
 */
class skill_points_changed extends \core\event\base {
    /**
     * Init the event data.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = points_history::TABLE;
    }

    /**
     * Create the event for a row of the points history.
     *
     * @param \stdClass $record The tool_skills_points_history record, with id.
     * @return self
     */
    public static function create_from_record(\stdClass $record): self {
        return self::create([
            'objectid' => $record->id,
            'context' => points_history::get_context($record->courseid ? (int) $record->courseid : null),
            'relateduserid' => $record->userid,
            'other' => [
                'skillid' => (int) $record->skill,
                'points' => (int) $record->points,
                'oldpoints' => (int) $record->oldpoints,
                'newpoints' => (int) $record->newpoints,
                'reason' => $record->reason,
                'method' => $record->method,
                'methodid' => $record->methodid !== null ? (int) $record->methodid : null,
            ],
        ]);
    }

    /**
     * Name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventskillpointschanged', 'tool_skills');
    }

    /**
     * Description of the event.
     *
     * @return string
     */
    public function get_description() {
        return "The skill points of the user with id '{$this->relateduserid}' for the skill with id " .
            "'{$this->other['skillid']}' changed from {$this->other['oldpoints']} to {$this->other['newpoints']} " .
            "(reason: {$this->other['reason']}).";
    }

    /**
     * The profile of the user, which shows their skills.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/user/profile.php', ['id' => $this->relateduserid]);
    }

    /**
     * Validate the event data.
     *
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
        foreach (['skillid', 'points', 'oldpoints', 'newpoints', 'reason'] as $key) {
            if (!isset($this->other[$key])) {
                throw new \coding_exception("The '{$key}' value must be set in other.");
            }
        }
    }

    /**
     * The points history is not part of course backups.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => points_history::TABLE, 'restore' => \core\event\base::NOT_MAPPED];
    }

    /**
     * The points history is not part of course backups.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
