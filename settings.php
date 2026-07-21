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
 * Tool Skills - Manage skills admin settings.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_category('skills', new lang_string('pluginname', 'tool_skills')));

    $settings = null; // Reset the settings.

    // Include the external page setting to manage skills.
    $automation = new admin_externalpage(
        'manageskills',
        get_string('manageskills', 'tool_skills'),
        new moodle_url('/admin/tool/skills/manage/list.php'),
        'tool/skills:manage'
    );

    $ADMIN->add('skills', $automation);

    // Load admin settings for all installed skilladdon subplugins.
    // Moodle does not iterate subplugin types in admin/settings/plugins.php,
    // so the parent plugin must trigger this manually.
    $skilladdonplugins = core_plugin_manager::instance()->get_plugins_of_type('skilladdon');
    foreach ($skilladdonplugins as $skilladdonplugin) {
        /** @var \tool_skills\plugininfo\skilladdon $skilladdonplugin */
        $skilladdonplugin->load_settings($ADMIN, 'skills', $hassiteconfig);
    }
}
