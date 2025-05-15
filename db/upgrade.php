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
 * DB authentication plugin upgrade code
 *
 * @package    tool_skills
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade tool_skills.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_tool_skills_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024020700) {
        $table = new xmldb_table('tool_skills_awardlogs');
        $field = new xmldb_field('skill', XMLDB_TYPE_INTEGER, 18, null, null, null, null, 'id');
        // Conditionally launch add field timecreated.
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

        }
        upgrade_plugin_savepoint(true, 2024020700, 'tool', 'skills');
    }

    if ($oldversion < 2024020802) {

        $table = new xmldb_table('tool_skills_awardlogs');
        $field = new xmldb_field('skill', XMLDB_TYPE_INTEGER, 18, null, null, null, null, 'id');
        // Conditionally launch add field timecreated.
        if ($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {

            $logs = $DB->get_records('tool_skills_awardlogs', ['method' => 'course']);
            $skills = $DB->get_records('tool_skills_courses', []);

            foreach ($logs as $log) {
                if (isset($skills[$log->methodid])) {
                    $skill = $skills[$log->methodid]->skill;
                    $DB->update_record('tool_skills_awardlogs', ['id' => $log->id, 'skill' => $skill]);
                }
            }
        }
        upgrade_plugin_savepoint(true, 2024020802, 'tool', 'skills');
    }

    return true;
}
