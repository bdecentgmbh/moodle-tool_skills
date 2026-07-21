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
 * Tool Skills - PHPUnit tests for the privacy provider.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use tool_skills\privacy\provider;

/**
 * Unit tests for \tool_skills\privacy\provider.
 *
 * @covers \tool_skills\privacy\provider
 */
final class privacy_test extends \core_privacy\tests\provider_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Build the DB fixtures needed by most tests:
     *   - A course
     *   - A skill
     *   - A tool_skills_courses record linking them
     *   - A tool_skills_userpoints record
     *   - A tool_skills_awardlogs record
     *
     * Returns an object with properties: course, skill, user, courseskillid.
     */
    private function create_full_fixture(\stdClass $user = null): \stdClass {
        global $DB;

        if ($user === null) {
            $user = $this->getDataGenerator()->create_user();
        }

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        // Create skill.
        $skillid = $DB->insert_record('tool_skills', (object)[
            'name'         => 'Privacy Skill',
            'identitykey'  => 'privsk' . rand(1, 9999),
            'description'  => '',
            'status'       => 1,
            'categories'   => '[]',
            'learningtime' => '',
            'levelscount'  => 0,
            'archived'     => 0,
            'timearchived' => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        // Link skill to course.
        $courseskillid = $DB->insert_record('tool_skills_courses', (object)[
            'courseid'       => $course->id,
            'skill'          => $skillid,
            'status'         => 1,
            'uponcompletion' => skills::COMPLETIONPOINTS,
            'points'         => 50,
            'level'          => 0,
            'timemodified'   => time(),
        ]);

        // Insert user points.
        $DB->insert_record('tool_skills_userpoints', (object)[
            'skill'        => $skillid,
            'userid'       => $user->id,
            'points'       => 50,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        // Insert award log referencing the tool_skills_courses record id.
        $DB->insert_record('tool_skills_awardlogs', (object)[
            'skill'       => $skillid,
            'userid'      => $user->id,
            'points'      => 50,
            'methodid'    => $courseskillid,
            'method'      => 'course',
            'timecreated' => time(),
        ]);

        $result = new \stdClass();
        $result->course       = $course;
        $result->skillid      = $skillid;
        $result->user         = $user;
        $result->courseskillid = $courseskillid;
        return $result;
    }

    /**
     * Test get_metadata() declares both userpoints and awardlogs tables.
     */
    public function test_get_metadata_describes_userpoints_and_awardlogs(): void {
        $collection = new collection('tool_skills');
        $result = provider::get_metadata($collection);

        $this->assertInstanceOf(collection::class, $result);
        // Inspect the items by iterating and collecting table names.
        $tablenames = [];
        foreach ($result->get_collection() as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\database_table) {
                $tablenames[] = $item->get_name();
            }
        }
        $this->assertContains('tool_skills_userpoints', $tablenames);
        $this->assertContains('tool_skills_awardlogs', $tablenames);
    }

    /**
     * Test get_contexts_for_userid() returns the course context when the user has award data.
     */
    public function test_get_contexts_for_userid_returns_course_context(): void {
        $fixture = $this->create_full_fixture();
        $coursecontext = \context_course::instance($fixture->course->id);

        $contextlist = provider::get_contexts_for_userid($fixture->user->id);
        $contextids  = $contextlist->get_contextids();

        $this->assertContains((string) $coursecontext->id, $contextids);
    }

    /**
     * Test get_contexts_for_userid() returns empty list when user has no data.
     */
    public function test_get_contexts_for_userid_returns_empty_for_unknown_user(): void {
        $user = $this->getDataGenerator()->create_user();
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(0, $contextlist);
    }

    /**
     * Test delete_data_for_user() removes both userpoints and awardlogs for the user.
     */
    public function test_delete_data_for_user_removes_all_user_records(): void {
        global $DB;
        $fixture = $this->create_full_fixture();
        $coursecontext = \context_course::instance($fixture->course->id);

        // Build approved contextlist.
        $approvedcontextlist = new approved_contextlist(
            $fixture->user,
            'tool_skills',
            [$coursecontext->id]
        );

        provider::delete_data_for_user($approvedcontextlist);

        $this->assertFalse($DB->record_exists('tool_skills_userpoints', ['userid' => $fixture->user->id]));
        $this->assertFalse($DB->record_exists('tool_skills_awardlogs', ['userid' => $fixture->user->id]));
    }

    /**
     * Test delete_data_for_all_users_in_context() adjusts points for all users in a course context.
     */
    public function test_delete_data_for_all_users_in_context_clears_course_logs(): void {
        global $DB;
        $user1    = $this->getDataGenerator()->create_user();
        $user2    = $this->getDataGenerator()->create_user();
        $fixture1 = $this->create_full_fixture($user1);
        // Second user in the same course/skill.
        $DB->insert_record('tool_skills_userpoints', (object)[
            'skill'        => $fixture1->skillid,
            'userid'       => $user2->id,
            'points'       => 50,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('tool_skills_awardlogs', (object)[
            'skill'       => $fixture1->skillid,
            'userid'      => $user2->id,
            'points'      => 50,
            'methodid'    => $fixture1->courseskillid,
            'method'      => 'course',
            'timecreated' => time(),
        ]);

        $coursecontext = \context_course::instance($fixture1->course->id);
        provider::delete_data_for_all_users_in_context($coursecontext);

        // Award logs for the course method should be gone.
        $this->assertFalse($DB->record_exists('tool_skills_awardlogs', [
            'methodid' => $fixture1->courseskillid, 'method' => 'course',
        ]));
    }
}
