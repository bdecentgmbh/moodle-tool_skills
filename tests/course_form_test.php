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
 * Tool Skills - PHPUnit tests for the course skill assignment form.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use tool_skills\form\course_form;

/**
 * Unit tests for \tool_skills\form\course_form::process_dynamic_submission().
 *
 * @covers \tool_skills\form\course_form
 */
final class course_form_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Insert a minimal skill record and return its id.
     *
     * @return int
     */
    private function create_skill(): int {
        global $DB;
        static $n = 0;
        $n++;
        return $DB->insert_record('tool_skills', (object)[
            'name'         => 'Skill ' . $n,
            'identitykey'  => 'cfk' . $n,
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
    }

    /**
     * Build the dynamic form seeded with the given AJAX submission data.
     *
     * The eighth constructor argument ($isajaxsubmission) is left false so the access check is not
     * re-run here; this test targets the write-target resolution in process_dynamic_submission(),
     * not the capability gate.
     *
     * @param array $formdata
     * @return course_form
     */
    private function build_form(array $formdata): course_form {
        // moodleform validates the sesskey from the request globals (not the ajax data) once the
        // _qf__ submit marker is present, so seed it there. resetAfterTest restores the superglobals.
        $_POST['sesskey'] = sesskey();
        $_GET['sesskey'] = sesskey();
        return new course_form(null, null, 'post', '', null, true, $formdata);
    }

    /**
     * A crafted submission carrying another course's tool_skills_courses id must not repoint that
     * row into the submitter's course. The write target is resolved from the (authorised) courseid
     * and skill, never the raw id, so the victim row stays with its own course.
     */
    public function test_process_rejects_foreign_course_skill_id(): void {
        global $DB;

        $coursea = $this->getDataGenerator()->create_course(); // Attacker's course.
        $courseb = $this->getDataGenerator()->create_course(); // Victim's course.
        $skill = $this->create_skill();

        // Victim: skill assigned to course B.
        $victimid = $DB->insert_record('tool_skills_courses', (object)[
            'courseid'       => $courseb->id,
            'skill'          => $skill,
            'status'         => skills::STATUS_ENABLE,
            'uponcompletion' => skills::COMPLETIONPOINTS,
            'points'         => 10,
            'level'          => 0,
            'timemodified'   => time(),
        ]);

        // Craft a submission authorised for course A but naming course B's row via id.
        $form = $this->build_form([
            '_qf__tool_skills_form_course_form' => 1,
            'sesskey'        => sesskey(),
            'courseid'       => $coursea->id,
            'skill'          => $skill,
            'id'             => $victimid,
            'status'         => skills::STATUS_ENABLE,
            'uponcompletion' => skills::COMPLETIONPOINTS,
            'points'         => 50,
            'level'          => 0,
        ]);
        $form->process_dynamic_submission();

        // The victim row is untouched: still attached to course B with its original points.
        $victim = $DB->get_record('tool_skills_courses', ['id' => $victimid]);
        $this->assertEquals($courseb->id, $victim->courseid);
        $this->assertEquals(10, $victim->points);

        // Course A received its own, separate assignment for the skill rather than hijacking B's.
        $arow = $DB->get_record('tool_skills_courses', ['courseid' => $coursea->id, 'skill' => $skill]);
        $this->assertNotEmpty($arow);
        $this->assertNotEquals($victimid, $arow->id);
    }

    /**
     * A legitimate edit of the caller's own course skill updates that row in place (no duplicate),
     * resolved by (courseid, skill).
     */
    public function test_process_updates_own_course_skill_in_place(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $skill = $this->create_skill();

        $rowid = $DB->insert_record('tool_skills_courses', (object)[
            'courseid'       => $course->id,
            'skill'          => $skill,
            'status'         => skills::STATUS_ENABLE,
            'uponcompletion' => skills::COMPLETIONPOINTS,
            'points'         => 10,
            'level'          => 0,
            'timemodified'   => time(),
        ]);

        $form = $this->build_form([
            '_qf__tool_skills_form_course_form' => 1,
            'sesskey'        => sesskey(),
            'courseid'       => $course->id,
            'skill'          => $skill,
            'id'             => $rowid,
            'status'         => skills::STATUS_ENABLE,
            'uponcompletion' => skills::COMPLETIONPOINTS,
            'points'         => 75,
            'level'          => 0,
        ]);
        $form->process_dynamic_submission();

        // Exactly one row for this course+skill, updated to the new points.
        $rows = $DB->get_records('tool_skills_courses', ['courseid' => $course->id, 'skill' => $skill]);
        $this->assertCount(1, $rows);
        $this->assertEquals(75, reset($rows)->points);
    }
}
