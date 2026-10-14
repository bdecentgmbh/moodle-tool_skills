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
 * Tool skills - Ad-hoc task importing the points earned before the points history started.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\task;

/**
 * Imports the award logs (and the logs of addons such as decay) into the points history, once, after the upgrade
 * that started the history.
 */
class import_points_history extends \core\task\adhoc_task {
    /**
     * Run the import.
     *
     * @return void
     */
    public function execute() {
        $historystart = (int) get_config('tool_skills', 'historystart');
        if (!$historystart) {
            return;
        }
        $count = \tool_skills\local\points_history_import::import_all($historystart);
        mtrace("Imported {$count} rows into the skill points history.");
    }
}
