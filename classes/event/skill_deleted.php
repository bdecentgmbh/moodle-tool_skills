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
 * Tool skills - Event: deleted a skill.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\event;

/**
 * A skill was deleted, with its levels, user points, award logs and points history.
 *
 * @property-read array $other {
 *      - string name: name of the deleted skill.
 * }
 */
class skill_deleted extends skill_base {
    /**
     * Init the event data.
     *
     * @return void
     */
    protected function init() {
        parent::init();
        $this->data['crud'] = 'd';
    }

    /**
     * Name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventskilldeleted', 'tool_skills');
    }

    /**
     * Description of the event.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' deleted the skill with id '{$this->objectid}'.";
    }

    /**
     * The skills list, since the skill is gone.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/skills/manage/list.php');
    }
}
