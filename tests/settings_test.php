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
 * Unit tests for the Skills section of Site administration.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Tests for the Skills section of Site administration (settings.php).
 *
 * @coversNothing
 */
final class settings_test extends \advanced_testcase {
    /**
     * Site administrators find Skills under Plugins > Admin tools, with its General settings.
     */
    public function test_site_administrators(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $root = admin_get_root(true, false);

        $this->assertNotNull($root->locate('tools')->locate('skills'));
        $this->assertTrue($root->locate('manageskills')->check_access());
        $this->assertNotNull($root->locate('tool_skills_settings'));
    }

    /**
     * Managers, who are no site administrators, find Manage skills in a Skills section of Site administration,
     * without the General settings (SKI-231). Users without a Skills capability do not see the section.
     */
    public function test_managers_and_other_users(): void {
        global $DB;
        $this->resetAfterTest();
        $manager = $this->getDataGenerator()->create_user();
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        $this->getDataGenerator()->role_assign($roleid, $manager->id, \context_system::instance()->id);
        $this->setUser($manager);
        $root = admin_get_root(true, false);

        $this->assertFalse(has_capability('moodle/site:config', \context_system::instance()));
        // Moodle has no Plugins > Admin tools for them.
        $this->assertNull($root->locate('tools'));
        $this->assertNotNull($root->locate('skills'));
        $this->assertTrue($root->locate('skills')->check_access());
        $this->assertTrue($root->locate('manageskills')->check_access());
        $this->assertNull($root->locate('tool_skills_settings'));

        $this->setUser($this->getDataGenerator()->create_user());
        $root = admin_get_root(true, false);
        $this->assertFalse($root->locate('skills')->check_access());
        $this->assertFalse($root->locate('manageskills')->check_access());
    }
}
