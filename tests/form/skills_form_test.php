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
 * Unit tests for the validation of the skill form.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\form;

#[\PHPUnit\Framework\Attributes\CoversClass(skills_form::class)]
/**
 * Tests for the validation of the skill form.
 *
 * @covers \tool_skills\form\skills_form
 */
final class skills_form_test extends \advanced_testcase {
    /**
     * Level points, and the levels whose points the form rejects.
     *
     * @return array
     */
    public static function levels_provider(): array {
        return [
            'increasing' => [[0, 10, 50], []],
            'one level' => [[20], []],
            'lower than the level before' => [[10, 50, 30], [3]],
            'equal to the level before' => [[0, 10, 10], [3]],
            'second level lower than the first' => [[20, 10, 30], [2]],
            'all the same' => [[5, 5, 5], [2, 3]],
        ];
    }

    /**
     * Each level needs more points than the level before it (SKI-230).
     *
     * @dataProvider levels_provider
     * @param int[] $points Points of the levels 1, 2, ...
     * @param int[] $rejected Numbers of the levels with an error.
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('levels_provider')]
    public function test_level_points_increase(array $points, array $rejected): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $levels = [];
        foreach ($points as $i => $value) {
            $levels[$i + 1] = ['id' => '', 'name' => 'Level ' . ($i + 1), 'points' => (string) $value, 'color' => ''];
        }
        $form = new skills_form(null, ['id' => 0]);
        $errors = $form->validation(['identitykey' => 'communication', 'color' => '', 'levels' => $levels], []);

        $expected = array_map(fn($i) => "levels[$i][points]", $rejected);
        $this->assertSame($expected, array_keys($errors));
        foreach ($rejected as $i) {
            $this->assertSame(
                get_string('error:levelpoints', 'tool_skills', $points[$i - 2]),
                $errors["levels[$i][points]"]
            );
        }
    }
}
