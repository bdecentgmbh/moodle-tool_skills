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
 * Tool skills - Event: a user reached a skill level.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\event;

use tool_skills\points_history;

/**
 * A user reached a higher level of a skill. The object is the level reached.
 *
 * @property-read array $other {
 *      - int skillid: the skill.
 *      - int|null previouslevelid: the level before, null when the user had none.
 *      - int points: the user's points after the change.
 *      - int historyid: the row of the points history that raised the level.
 * }
 */
class skill_level_reached extends \core\event\base {
    /**
     * Init the event data.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_skills_levels';
    }

    /**
     * Create the event for a row of the points history that raised the level.
     *
     * @param \stdClass $record The tool_skills_points_history record, with id.
     * @return self
     */
    public static function create_from_record(\stdClass $record): self {
        return self::create([
            'objectid' => $record->newlevel,
            'context' => points_history::get_context($record->courseid ? (int) $record->courseid : null),
            'relateduserid' => $record->userid,
            'other' => [
                'skillid' => (int) $record->skill,
                'previouslevelid' => $record->oldlevel !== null ? (int) $record->oldlevel : null,
                'points' => (int) $record->newpoints,
                'historyid' => (int) $record->id,
            ],
        ]);
    }

    /**
     * Name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventskilllevelreached', 'tool_skills');
    }

    /**
     * Description of the event.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->relateduserid}' reached the level with id '{$this->objectid}' of the skill " .
            "with id '{$this->other['skillid']}' with {$this->other['points']} points.";
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
        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' (level) must be set.');
        }
        foreach (['skillid', 'points', 'historyid'] as $key) {
            if (!isset($this->other[$key])) {
                throw new \coding_exception("The '{$key}' value must be set in other.");
            }
        }
    }

    /**
     * Levels are not part of course backups.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'tool_skills_levels', 'restore' => \core\event\base::NOT_MAPPED];
    }

    /**
     * Levels are not part of course backups.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
