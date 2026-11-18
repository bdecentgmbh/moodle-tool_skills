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
 * Tool skills - Base of the events about a skill.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\event;

/**
 * Base of the events about a skill, fired in the system context.
 */
abstract class skill_base extends \core\event\base {
    /**
     * Init the event data.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'tool_skills';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Create the event for a skill.
     *
     * @param int $skillid
     * @param array $other Additional data.
     * @return static
     */
    public static function create_from_skill(int $skillid, array $other = []) {
        return static::create([
            'objectid' => $skillid,
            'context' => \context_system::instance(),
            'other' => $other,
        ]);
    }

    /**
     * The skill's edit page.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/skills/manage/edit.php', ['id' => $this->objectid]);
    }

    /**
     * Skills are not part of course backups.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'tool_skills', 'restore' => \core\event\base::NOT_MAPPED];
    }

    /**
     * Skills are not part of course backups.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
