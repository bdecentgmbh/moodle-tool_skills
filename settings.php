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


// The Skills section is in the admin tree for everyone: Moodle shows a user only the pages their capabilities allow,
// so users who may manage skills, targets or decay without being site administrators find them there too. The
// General settings page stays with the site administrators. Moodle adds Plugins > Admin tools for site
// administrators only; for the others, Skills is a section of its own in Site administration.
$ADMIN->add($ADMIN->locate('tools') ? 'tools' : 'root', new admin_category('skills', new lang_string('pluginname', 'tool_skills')));

$settings = null; // Reset the settings.

// Include the external page setting to manage skills.
$ADMIN->add('skills', new admin_externalpage(
    'manageskills',
    get_string('manageskills', 'tool_skills'),
    new moodle_url('/admin/tool/skills/manage/list.php'),
    'tool/skills:manage'
));

if ($hassiteconfig) {
    // General settings.
    $generalpage = new admin_settingpage('tool_skills_settings', new lang_string('generalsettings', 'tool_skills'));
    if ($ADMIN->fulltree) {
        // What the Skills section of the user profile shows.
        $generalpage->add(new admin_setting_configselect(
            'tool_skills/profiledisplay',
            new lang_string('profiledisplay', 'tool_skills'),
            new lang_string('profiledisplay_desc', 'tool_skills'),
            \tool_skills\helper::PROFILEDISPLAY_DETAILED,
            [
                \tool_skills\helper::PROFILEDISPLAY_DETAILED => new lang_string('profiledisplaydetailed', 'tool_skills'),
                \tool_skills\helper::PROFILEDISPLAY_TOTALS => new lang_string('profiledisplaytotals', 'tool_skills'),
                \tool_skills\helper::PROFILEDISPLAY_DISABLED => new lang_string('disabled', 'tool_skills'),
            ]
        ));
    }
    $ADMIN->add('skills', $generalpage);
}

// Load admin settings for all installed skilladdon subplugins: their pages, with the capabilities they need.
// Moodle does not iterate subplugin types in admin/settings/plugins.php,
// so the parent plugin must trigger this manually.
$skilladdonplugins = core_plugin_manager::instance()->get_plugins_of_type('skilladdon');
foreach ($skilladdonplugins as $skilladdonplugin) {
    /** @var \tool_skills\plugininfo\skilladdon $skilladdonplugin */
    $skilladdonplugin->load_settings($ADMIN, 'skills', $hassiteconfig);
}
