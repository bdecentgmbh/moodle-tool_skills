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

    if ($oldversion < 2025042301) {
        // Migrate levelscount from "extra levels beyond base" to "total levels".
        // Previously, selecting N created N+1 levels (base + N extras), so levelscount stored N.
        // Now levelscount stores the total number of levels, so increment all existing values by 1.
        $DB->execute("UPDATE {tool_skills} SET levelscount = levelscount + 1");
        upgrade_plugin_savepoint(true, 2025042301, 'tool', 'skills');
    }

    if ($oldversion < 2026032401) {
        // Add color field to tool_skills.
        $table = new xmldb_table('tool_skills');
        $field = new xmldb_field('color', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'learningtime');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add color field to tool_skills_levels.
        $table = new xmldb_table('tool_skills_levels');
        $field = new xmldb_field('color', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'points');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026032401, 'tool', 'skills');
    }


    return true;
}
