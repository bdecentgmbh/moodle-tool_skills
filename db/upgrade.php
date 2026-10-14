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

    if ($oldversion < 2026061500) {
        // Add the color field to the skills table.
        $table = new xmldb_table('tool_skills');
        $field = new xmldb_field('color', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'learningtime');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add the color field to the levels table.
        $table = new xmldb_table('tool_skills_levels');
        $field = new xmldb_field('color', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'points');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026061500, 'tool', 'skills');
    }

    if ($oldversion < 2026072101) {
        // Add indexes on the award-log lookup columns (queried on every completion) and on levels.skill.
        $table = new xmldb_table('tool_skills_awardlogs');
        $index = new xmldb_index(
            'skill-userid-method-methodid',
            XMLDB_INDEX_NOTUNIQUE,
            ['skill', 'userid', 'method', 'methodid']
        );
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('methodid-method', XMLDB_INDEX_NOTUNIQUE, ['methodid', 'method']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('tool_skills_levels');
        $index = new xmldb_index('skill', XMLDB_INDEX_NOTUNIQUE, ['skill']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026072101, 'tool', 'skills');
    }

    if ($oldversion < 2026101401) {
        // Points history: one row per change of a user's skill points.
        $table = new xmldb_table('tool_skills_points_history');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('skill', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('points', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('oldpoints', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('newpoints', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('oldlevel', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('newlevel', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('reason', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('method', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('methodid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('sourcename', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('skill', XMLDB_KEY_FOREIGN, ['skill'], 'tool_skills', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_index('userid-skill-timecreated', XMLDB_INDEX_NOTUNIQUE, ['userid', 'skill', 'timecreated']);
        $table->add_index('method-methodid', XMLDB_INDEX_NOTUNIQUE, ['method', 'methodid']);
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Changes are recorded from now on. Earlier points are imported from the award logs (and the logs of
        // addons such as decay) in the background, once all plugins are upgraded.
        if (!get_config('tool_skills', 'historystart')) {
            set_config('historystart', time(), 'tool_skills');
            \core\task\manager::queue_adhoc_task(new \tool_skills\task\import_points_history(), true);
        }

        upgrade_plugin_savepoint(true, 2026101401, 'tool', 'skills');
    }

    return true;
}
