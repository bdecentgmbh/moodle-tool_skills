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
 * Tool skills - Tests of the points history and its events.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

#[\PHPUnit\Framework\Attributes\CoversClass(points_history::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(points_change::class)]
/**
 * Tests of the points history and its events.
 *
 * @covers \tool_skills\points_history
 * @covers \tool_skills\points_change
 * @covers \tool_skills\skills::set_userskill_points
 * @covers \tool_skills\event\skill_points_changed
 * @covers \tool_skills\event\skill_level_reached
 */
final class points_history_test extends \advanced_testcase {
    /** @var int Skill with the levels Bronze (10) and Silver (20). */
    private int $skillid;

    /** @var array Level ids by name. */
    private array $levels = [];

    /** @var \stdClass Course with completion enabled. */
    private \stdClass $course;

    /** @var \stdClass Learner enrolled in the course. */
    private \stdClass $user;

    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->skillid = (int) $DB->insert_record('tool_skills', (object) [
            'name' => 'Product knowledge', 'identitykey' => 'product', 'description' => '', 'status' => 1,
            'categories' => '[]', 'learningtime' => '', 'levelscount' => 2, 'archived' => 0, 'timearchived' => 0,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        foreach (['Bronze' => 10, 'Silver' => 20] as $name => $points) {
            $this->levels[$name] = (int) $DB->insert_record('tool_skills_levels', (object) [
                'skill' => $this->skillid, 'name' => $name, 'points' => $points, 'status' => 1,
                'timecreated' => time(), 'timemodified' => time(),
            ]);
        }
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1, 'fullname' => 'Product basics']);
        $this->user = $this->getDataGenerator()->create_and_enrol($this->course);
    }

    /**
     * Enable the skill in the course, complete the course and run the award.
     *
     * @param int $uponcompletion One of the skills::COMPLETION* constants.
     * @param int $points Points of the course.
     * @param int $level Level for the set and force level options.
     * @return int ID of the tool_skills_courses record.
     */
    private function award_course(int $uponcompletion = skills::COMPLETIONPOINTS, int $points = 15, int $level = 0): int {
        global $DB;
        $courseskillid = (int) $DB->insert_record('tool_skills_courses', (object) [
            'courseid' => $this->course->id, 'skill' => $this->skillid, 'status' => 1,
            'uponcompletion' => $uponcompletion, 'points' => $points, 'level' => $level, 'timemodified' => time(),
        ]);
        $DB->insert_record('course_completions', (object) [
            'userid' => $this->user->id, 'course' => $this->course->id, 'timeenrolled' => time(),
            'timestarted' => time(), 'timecompleted' => time(), 'reaggregate' => 0,
        ]);
        $cs = courseskills::get($this->course->id);
        $cs->manage_course_completions($this->user->id, $cs->get_instance_skills(), null);
        return $courseskillid;
    }

    /**
     * History rows of the user, oldest first.
     *
     * @return array
     */
    private function history(): array {
        global $DB;
        return array_values($DB->get_records(points_history::TABLE, ['userid' => $this->user->id], 'id ASC'));
    }

    public function test_course_award_is_recorded_with_its_source_and_level(): void {
        $sink = $this->redirectEvents();
        $courseskillid = $this->award_course();
        $events = $sink->get_events();
        $sink->close();

        $rows = $this->history();
        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertEquals(points_history::REASON_AWARD, $row->reason);
        $this->assertEquals('course', $row->method);
        $this->assertEquals($courseskillid, $row->methodid);
        $this->assertEquals($this->course->id, $row->courseid);
        $this->assertNull($row->cmid);
        $this->assertEquals('Product basics', $row->sourcename);
        $this->assertEquals([15, 0, 15], [(int) $row->points, (int) $row->oldpoints, (int) $row->newpoints]);
        $this->assertNull($row->oldlevel);
        $this->assertEquals($this->levels['Bronze'], $row->newlevel);

        $changed = array_values(array_filter($events, fn($e) => $e instanceof event\skill_points_changed));
        $reached = array_values(array_filter($events, fn($e) => $e instanceof event\skill_level_reached));
        $this->assertCount(1, $changed);
        $this->assertCount(1, $reached);
        $this->assertEquals($row->id, $changed[0]->objectid);
        $this->assertEquals($this->user->id, $changed[0]->relateduserid);
        $this->assertEquals(\context_course::instance($this->course->id)->id, $changed[0]->contextid);
        $this->assertEquals(['skillid' => $this->skillid, 'points' => 15, 'oldpoints' => 0, 'newpoints' => 15,
            'reason' => 'award', 'method' => 'course', 'methodid' => $courseskillid], $changed[0]->other);
        $this->assertEquals($this->levels['Bronze'], $reached[0]->objectid);
        $this->assertNull($reached[0]->other['previouslevelid']);
        $this->assertNotEmpty($changed[0]->get_description());
        $this->assertInstanceOf(\moodle_url::class, $reached[0]->get_url());
    }

    public function test_changed_course_points_are_revoked_and_awarded_again(): void {
        global $DB;
        $courseskillid = $this->award_course();
        $DB->set_field('tool_skills_courses', 'points', 25, ['id' => $courseskillid]);
        $cs = courseskills::get($this->course->id);
        $cs->manage_course_completions($this->user->id, $cs->get_instance_skills(), null);

        $rows = $this->history();
        $this->assertCount(3, $rows);
        $this->assertEquals(['award', 'revoke', 'award'], array_column($rows, 'reason'));
        $this->assertEquals([15, -15, 25], array_map('intval', array_column($rows, 'points')));
        $this->assertEquals(25, (int) end($rows)->newpoints);
        $this->assertEquals($this->levels['Silver'], end($rows)->newlevel);
        // Earned in the period: what the course gives now.
        $this->assertEquals(25, array_sum(array_column($rows, 'points')));
    }

    public function test_disabling_the_skill_in_the_course_revokes_its_points(): void {
        $this->award_course();
        $cs = courseskills::get($this->course->id);
        $cs->manage_course_completions($this->user->id, $cs->get_instance_skills(), false);

        $rows = $this->history();
        $this->assertCount(2, $rows);
        $this->assertEquals(points_history::REASON_REVOKE, $rows[1]->reason);
        $this->assertEquals(-15, (int) $rows[1]->points);
        $this->assertEquals($this->course->id, $rows[1]->courseid);
        $this->assertEquals(0, (int) $rows[1]->newpoints);
        $this->assertNull($rows[1]->newlevel);
    }

    public function test_set_and_force_level_are_recorded_as_awards(): void {
        $this->award_course(skills::COMPLETIONFORCELEVEL, 0, $this->levels['Silver']);
        $rows = $this->history();
        $this->assertCount(1, $rows);
        $this->assertEquals(points_history::REASON_AWARD, $rows[0]->reason);
        $this->assertEquals(20, (int) $rows[0]->points);
        $this->assertEquals($this->levels['Silver'], $rows[0]->newlevel);
    }

    public function test_changes_without_a_source_are_recorded_as_other_and_unchanged_points_not_at_all(): void {
        $skill = skills::get($this->skillid);
        $skill->set_userskill_points($this->user->id, 12);
        $skill->set_userskill_points($this->user->id, 12);

        $rows = $this->history();
        $this->assertCount(1, $rows);
        $this->assertEquals(points_history::REASON_OTHER, $rows[0]->reason);
        $this->assertNull($rows[0]->method);
        $this->assertNull($rows[0]->courseid);
    }

    public function test_level_reached_fires_only_when_the_level_rises(): void {
        $skill = skills::get($this->skillid);
        $skill->set_userskill_points($this->user->id, 22);

        $sink = $this->redirectEvents();
        $skill->set_userskill_points($this->user->id, 12, new points_change(points_history::REASON_DECAY, 'decay', 5));
        $skill->set_userskill_points($this->user->id, 15);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(2, array_filter($events, fn($e) => $e instanceof event\skill_points_changed));
        $this->assertCount(0, array_filter($events, fn($e) => $e instanceof event\skill_level_reached));
        $rows = $this->history();
        $this->assertEquals([$this->levels['Silver'], $this->levels['Bronze']], [$rows[1]->oldlevel, $rows[1]->newlevel]);
        // Without a course, the event is in the system context.
        $this->assertEquals(\context_system::instance()->id, $events[0]->contextid);
    }

    public function test_points_change_keeps_its_source_with_another_reason(): void {
        $change = new points_change(points_history::REASON_AWARD, 'course', 3, 4, null, 'Course', 7);
        $revoke = $change->with_reason(points_history::REASON_REVOKE);
        $this->assertEquals(points_history::REASON_REVOKE, $revoke->reason);
        $this->assertEquals(
            ['course', 3, 4, null, 'Course', 7],
            [$revoke->method, $revoke->methodid, $revoke->courseid, $revoke->cmid, $revoke->sourcename, $revoke->usermodified]
        );
    }

    public function test_history_is_deleted_with_the_user_and_with_the_skill(): void {
        global $DB;
        $this->award_course();
        $other = $this->getDataGenerator()->create_user();
        skills::get($this->skillid)->set_userskill_points($other->id, 5);

        delete_user($this->user);
        $this->assertFalse($DB->record_exists(points_history::TABLE, ['userid' => $this->user->id]));
        $this->assertTrue($DB->record_exists(points_history::TABLE, ['userid' => $other->id]));

        skills::get($this->skillid)->delete_skill();
        $this->assertFalse($DB->record_exists(points_history::TABLE, ['skill' => $this->skillid]));
    }

    public function test_history_of_a_deleted_course_is_kept_with_its_name(): void {
        global $DB;
        $this->award_course();
        delete_course($this->course, false);

        $rows = $this->history();
        $this->assertCount(1, $rows);
        $this->assertEquals('Product basics', $rows[0]->sourcename);
        $this->assertEquals(\context_system::instance()->id, points_history::get_context((int) $rows[0]->courseid)->id);
        $this->assertEquals(15, (int) $DB->get_field('tool_skills_userpoints', 'points', ['userid' => $this->user->id]));
    }
}
