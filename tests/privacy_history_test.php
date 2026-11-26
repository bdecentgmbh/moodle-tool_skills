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
 * Tool skills - Privacy tests of the points history.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use tool_skills\privacy\provider;

#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
/**
 * Privacy tests of the points history: the learner's history in their user context, the acting user of manual
 * changes in the system context, and a course's rows with the course's data.
 *
 * @covers \tool_skills\privacy\provider
 */
final class privacy_history_test extends \core_privacy\tests\provider_testcase {
    /** @var int */
    private int $skillid;

    /** @var \stdClass Learner. */
    private \stdClass $learner;

    /** @var \stdClass Administrator who made a manual change. */
    private \stdClass $manager;

    /** @var \stdClass */
    private \stdClass $course;

    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();

        $this->skillid = (int) $DB->insert_record('tool_skills', (object) [
            'name' => 'Safety', 'identitykey' => 'safety', 'description' => '', 'status' => 1, 'categories' => '[]',
            'learningtime' => '', 'levelscount' => 0, 'archived' => 0, 'timearchived' => 0,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $this->learner = $this->getDataGenerator()->create_user();
        $this->manager = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course(['fullname' => 'Safety basics']);

        $skill = skills::get($this->skillid);
        $skill->set_userskill_points($this->learner->id, 10, new points_change(
            points_history::REASON_AWARD,
            'course',
            1,
            (int) $this->course->id,
            null,
            'Safety basics'
        ));
        $skill->set_userskill_points($this->learner->id, 7, new points_change(
            points_history::REASON_DECAY,
            'decay',
            null,
            null,
            null,
            null,
            (int) $this->manager->id
        ));
    }

    public function test_metadata_declares_the_history(): void {
        $tables = [];
        foreach (provider::get_metadata(new collection('tool_skills'))->get_collection() as $item) {
            $tables[] = $item->get_name();
        }
        $this->assertContains(points_history::TABLE, $tables);
    }

    public function test_contexts_and_users(): void {
        $usercontext = \context_user::instance($this->learner->id);
        $system = \context_system::instance();

        $this->assertContains((string) $usercontext->id, provider::get_contexts_for_userid($this->learner->id)->get_contextids());
        $this->assertNotContains((string) $system->id, provider::get_contexts_for_userid($this->learner->id)->get_contextids());
        $this->assertEquals([(string) $system->id], provider::get_contexts_for_userid($this->manager->id)->get_contextids());

        $userlist = new userlist($usercontext, 'tool_skills');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$this->learner->id], $userlist->get_userids());

        $userlist = new userlist($system, 'tool_skills');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$this->manager->id], $userlist->get_userids());
    }

    public function test_export(): void {
        $usercontext = \context_user::instance($this->learner->id);
        $this->export_context_data_for_user($this->learner->id, $usercontext, 'tool_skills');
        $data = writer::with_context($usercontext)->get_data([get_string('privacy:history', 'tool_skills')]);
        $this->assertCount(2, $data->changes);
        $this->assertEquals('Safety', $data->changes[0]->skill);
        $this->assertEquals(10, $data->changes[0]->points);
        $this->assertEquals('Safety basics', $data->changes[0]->source);
        $this->assertEquals('decay', $data->changes[1]->reason);

        $system = \context_system::instance();
        $this->export_context_data_for_user($this->manager->id, $system, 'tool_skills');
        $data = writer::with_context($system)->get_data([get_string('privacy:history', 'tool_skills')]);
        $this->assertCount(1, $data->changes);
        $this->assertEquals(-3, $data->changes[0]->points);
    }

    public function test_deleting_the_learner_removes_their_history(): void {
        global $DB;
        $usercontext = \context_user::instance($this->learner->id);
        provider::delete_data_for_user(new approved_contextlist($this->learner, 'tool_skills', [$usercontext->id]));
        $this->assertEquals(0, $DB->count_records(points_history::TABLE, ['userid' => $this->learner->id]));
    }

    public function test_deleting_the_manager_keeps_the_history_without_them(): void {
        global $DB;
        $system = \context_system::instance();
        provider::delete_data_for_users(new approved_userlist($system, 'tool_skills', [$this->manager->id]));
        $this->assertEquals(2, $DB->count_records(points_history::TABLE, ['userid' => $this->learner->id]));
        $this->assertFalse($DB->record_exists(points_history::TABLE, ['usermodified' => $this->manager->id]));

        // Deleting everything in the system context anonymises every manual change.
        $DB->set_field(points_history::TABLE, 'usermodified', $this->manager->id, ['reason' => 'decay']);
        provider::delete_data_for_all_users_in_context($system);
        $this->assertFalse($DB->record_exists(points_history::TABLE, ['usermodified' => $this->manager->id]));
    }

    public function test_deleting_a_course_context_removes_its_rows(): void {
        global $DB;
        provider::delete_data_for_all_users_in_context(\context_course::instance($this->course->id));
        $this->assertFalse($DB->record_exists(points_history::TABLE, ['courseid' => $this->course->id]));
        $this->assertEquals(1, $DB->count_records(points_history::TABLE, ['userid' => $this->learner->id]));

        provider::delete_data_for_all_users_in_context(\context_user::instance($this->learner->id));
        $this->assertEquals(0, $DB->count_records(points_history::TABLE, ['userid' => $this->learner->id]));
    }
}
