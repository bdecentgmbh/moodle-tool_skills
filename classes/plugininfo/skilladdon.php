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
 * Subplugin type for tool skills - defined.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\plugininfo;

/**
 * Skilladdon is subplugin of tool_skills.
 */
class skilladdon extends \core\plugininfo\base {

    /**
     * Returns the information about plugin availability
     *
     * True means that the plugin is enabled. False means that the plugin is
     * disabled. Null means that the information is not available, or the
     * plugin does not support configurable availability or the availability
     * can not be changed.
     *
     * @return null|bool
     */
    public function is_enabled() {
        return true;
    }

    /**
     * Should there be a way to uninstall the plugin via the administration UI.
     *
     * By default uninstallation is not allowed, plugin developers must enable it explicitly!
     *
     * @return bool
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Get the list of action plugins with its base class.
     *
     * @param string $method
     * @return array
     */
    public function get_plugins_base($method) {
        $plugins = \core_component::get_plugin_list('skilladdon');

        if (!empty($plugins)) {
            foreach ($plugins as $componentname => $pluginpath) {
                $classname = "skilladdon_$componentname\manage_skills";
                if (!class_exists($classname)) {
                    continue;
                }

                if (method_exists("skilladdon_$componentname\manage_skills", $method)) {
                    $instance = new $classname();
                    $extend[] = $instance;
                }
            }
        }
        return $extend ?? [];
    }
}
