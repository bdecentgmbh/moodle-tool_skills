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
 * Tool skills - Tests of the skill management events.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\event;

use tool_skills\skills;

#[\PHPUnit\Framework\Attributes\CoversClass(skill_created::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(skill_updated::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(skill_deleted::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(skill_archived::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(skill_restored::class)]
/**
 * Tests of the skill management events.
 *
 * @covers \tool_skills\event\skill_base
 * @covers \tool_skills\event\skill_created
 * @covers \tool_skills\event\skill_updated
 * @covers \tool_skills\event\skill_deleted
 * @covers \tool_skills\event\skill_archived
 * @covers \tool_skills\event\skill_restored
 */
final class skill_events_test extends \advanced_testcase {
    /**
     * Run a callback and return the events it fired.
     *
     * @param callable $callback
     * @return \core\event\base[]
     */
    private function events(callable $callback): array {
        $sink = $this->redirectEvents();
        $callback();
        $events = $sink->get_events();
        $sink->close();
        return $events;
    }

    /**
     * Form data of a skill without levels.
     *
     * @param string $name
     * @return \stdClass
     */
    private function formdata(string $name): \stdClass {
        return (object) [
            'name' => $name, 'identitykey' => strtolower($name), 'description' => '', 'status' => 1,
            'categories' => [], 'learningtime' => '', 'levelscount' => 0,
        ];
    }

    public function test_skill_lifecycle_fires_its_events(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $system = \context_system::instance();

        $skillid = 0;
        $events = $this->events(function () use (&$skillid) {
            $skillid = (int) skills::manage_instance($this->formdata('Safety'));
        });
        $this->assertCount(1, $events);
        $this->assertInstanceOf(skill_created::class, $events[0]);
        $this->assertEquals($skillid, $events[0]->objectid);
        $this->assertEquals($system->id, $events[0]->contextid);
        $this->assertEquals('c', $events[0]->crud);
        $this->assertEquals(new \moodle_url('/admin/tool/skills/manage/edit.php', ['id' => $skillid]), $events[0]->get_url());

        $events = $this->events(function () use ($skillid) {
            $data = $this->formdata('Safety');
            $data->id = $skillid;
            $data->description = 'Changed';
            skills::manage_instance($data);
            skills::get($skillid)->update_status(false);
        });
        $this->assertCount(2, $events);
        $this->assertContainsOnlyInstancesOf(skill_updated::class, $events);

        $events = $this->events(fn() => $this->assertTrue(skills::get($skillid)->archive_skill()));
        $this->assertInstanceOf(skill_archived::class, $events[0]);

        $events = $this->events(fn() => $this->assertTrue(skills::get($skillid)->active_skill()));
        $this->assertInstanceOf(skill_restored::class, $events[0]);

        $events = $this->events(fn() => $this->assertTrue(skills::get($skillid)->delete_skill()));
        $deleted = array_values(array_filter($events, fn($e) => $e instanceof skill_deleted));
        $this->assertCount(1, $deleted);
        $this->assertEquals('d', $deleted[0]->crud);
        $this->assertEquals('Safety', $deleted[0]->other['name']);
        $this->assertEquals(new \moodle_url('/admin/tool/skills/manage/list.php'), $deleted[0]->get_url());
        foreach ([$events[0], $deleted[0]] as $event) {
            $this->assertNotEmpty($event->get_name());
            $this->assertStringContainsString((string) $skillid, $event->get_description());
        }
    }
}
