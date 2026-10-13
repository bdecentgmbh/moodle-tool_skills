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
 * Tool Skills - PHPUnit tests for the extend_profile_skill hook.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\hook;

/**
 * Unit tests for adding and removing pills through the extend_profile_skill hook.
 *
 * @covers \tool_skills\hook\extend_profile_skill
 */
final class extend_profile_skill_test extends \advanced_testcase {
    /**
     * Create a hook instance for a fresh skill.
     *
     * @return extend_profile_skill
     */
    private function create_hook(): extend_profile_skill {
        global $DB;

        $skillid = $DB->insert_record('tool_skills', (object) [
            'name' => 'Communication', 'identitykey' => 'communication', 'description' => '',
            'status' => 1, 'categories' => '[]', 'levelscount' => 0,
            'archived' => 0, 'timearchived' => 0, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        return new extend_profile_skill(2, \tool_skills\skills::get($skillid), 40);
    }

    /**
     * The hook exposes the profile user, the skill and the points.
     */
    public function test_properties(): void {
        $this->resetAfterTest(true);
        $hook = $this->create_hook();

        $this->assertSame(2, $hook->userid);
        $this->assertSame('Communication', $hook->skill->get_name());
        $this->assertSame(40, $hook->points);
        $this->assertSame([], $hook->get_pills());
    }

    /**
     * Pills keep the order they were added in, a pill with a used name replaces it, and pills can be removed.
     */
    public function test_add_replace_and_remove_pills(): void {
        $this->resetAfterTest(true);
        $hook = $this->create_hook();

        $hook->add_pill('skilladdon_decay', '−5 decay', '5 points removed by decay', 'warning')
            ->add_pill('skilladdon_targets', 'Target: Level 2')
            ->add_pill('skilladdon_decay', '−7 decay');

        $this->assertSame([
            ['name' => 'skilladdon_decay', 'label' => '−7 decay', 'title' => '', 'variant' => 'secondary'],
            ['name' => 'skilladdon_targets', 'label' => 'Target: Level 2', 'title' => '', 'variant' => 'secondary'],
        ], $hook->get_pills());

        $hook->remove_pill('skilladdon_decay')->remove_pill('unknown');
        $this->assertSame(['skilladdon_targets'], array_column($hook->get_pills(), 'name'));
    }

    /**
     * A pill name must be usable in a CSS class.
     */
    public function test_invalid_name_rejected(): void {
        $this->resetAfterTest(true);
        $this->expectException(\coding_exception::class);
        $this->create_hook()->add_pill('decay pill', '−5');
    }

    /**
     * A pill must use one of the known colour variants.
     */
    public function test_invalid_variant_rejected(): void {
        $this->resetAfterTest(true);
        $this->expectException(\coding_exception::class);
        $this->create_hook()->add_pill('decay', '−5', '', 'primary');
    }
}
