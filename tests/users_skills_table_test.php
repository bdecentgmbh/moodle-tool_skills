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
 * Tool Skills - PHPUnit tests for the users points report table.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Renders the users points report table, which assembles its SQL from the user picture fields.
 *
 * @covers \tool_skills\table\users_skills
 */
final class users_skills_table_test extends \advanced_testcase {
    /**
     * The table query must run and list every user holding points for the skill.
     */
    public function test_report_lists_users_with_points(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();

        $skillid = $DB->insert_record('tool_skills', (object) [
            'name' => 'Communication', 'identitykey' => 'communication', 'description' => '', 'status' => 1,
            'categories' => '[]', 'learningtime' => '', 'levelscount' => 0, 'archived' => 0, 'timearchived' => 0,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $gen = $this->getDataGenerator();
        $anna = $gen->create_user(['firstname' => 'Anna', 'lastname' => 'Complete']);
        $ben = $gen->create_user(['firstname' => 'Ben', 'lastname' => 'Halfway']);
        $other = $gen->create_user(['firstname' => 'Nobody', 'lastname' => 'Elsewhere']);
        foreach ([[$anna, 90], [$ben, 50]] as [$user, $points]) {
            $DB->insert_record('tool_skills_userpoints', (object) [
                'skill' => $skillid, 'userid' => $user->id, 'points' => $points,
                'timecreated' => time(), 'timemodified' => time(),
            ]);
        }

        $PAGE->set_url(new \moodle_url('/admin/tool/skills/manage/usersreport.php', ['id' => $skillid]));
        $table = new \tool_skills\table\users_skills($skillid);
        $table->define_baseurl($PAGE->url);
        ob_start();
        $table->out(50, true);
        $html = ob_get_clean();

        $this->assertStringContainsString('Anna Complete', $html);
        $this->assertStringContainsString('Ben Halfway', $html);
        $this->assertStringNotContainsString('Nobody Elsewhere', $html);
        $this->assertStringContainsString('>90<', $html);
        $this->assertStringContainsString('>50<', $html);
        $this->assertSame(2, $table->totalrows);
    }
}
