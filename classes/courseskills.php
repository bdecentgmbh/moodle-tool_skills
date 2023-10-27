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
 * Tool skills - Course skills handler.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use completion_info;
use moodle_exception;
use stdClass;

/**
 * Manage the skills for courses. Trigger skills to assign point for users.
 */
class courseskills extends \tool_skills\allocation_method {

    /**
     * ID of the course skill record id.
     *
     * @var int
     */
    protected int $id;

    /**
     * ID of the course record id.
     *
     * @var int
     */
    protected int $courseid;


    /**
     * Constructor
     *
     * @param int $skillcourseid ID of the skill course record.
     */
    protected function __construct(int $courseid) {
        parent::__construct(); // Create a parent instance
        // Course id.
        $this->courseid = $courseid;

    }

    /**
     * Create the retunr the clas instance for this skillcourse id.
     *
     * @param int $skillcourseid
     * @return self
     */
    public static function get(int $courseid) : self {
        return new self($courseid);
    }

    /**
     * Create the retunr the clas instance for this skillcourse id.
     *
     * @param int $skillcourseid
     * @return self
     */
    public static function get_for_skill(int $skillid) : array {
        global $DB;

        $courses = $DB->get_records('tool_skills_courses', ['skill' => $skillid]);

        return array_map(fn($course) => new self($course->id), $courses);
    }

    /**
     * Get the course record for this courseid.
     *
     * @return stdClass Course record data.
     */
    public function get_course() : stdClass {
        return get_course($this->courseid);
    }

    /**
     * Fetch the skills assigned/enabled for this course.
     *
     * @param int $courseid
     * @return array
     */
    public function get_instance_skills(): array {
        global $DB;

        $skills = $DB->get_records('tool_skills_courses', ['courseid' => $this->courseid, 'status' => 1]);

        return array_map(fn($sk) => skills::get($sk->skill), $skills);
    }

    /**
     * Remove the course skills records.
     *
     * @return void
     */
    public function remove_instance_skills() {
        global $DB;

        $DB->delete_records('tool_skills_courses', ['courseid' => $this->courseid]);

        $this->get_logs()->delete_method_log($this->courseid, 'course');
    }

    /**
     * Get the skill course record.
     *
     * @return stdclass
     */
    public function build_data() {
        global $DB;

        if (!$this->instanceid) {
            throw new moodle_exception('skillcoursenotset', 'tool_skills');
        }
        // Fetch the skills course record.
        $record = $DB->get_record('tool_skills_courses', ['id' => $this->instanceid]);

        $this->data = $record;

        return $this->data;
    }

    /**
     * Fetch the user points.
     *
     * @return int
     */
    public function get_points() : ?int {

        $this->build_data(); // Build the data of the skill for this course.

        return $this->data->points ?? false;
    }

    /**
     * Manage the course completions to allocate the points to the courses skills.
     *
     * Given course is completed for this user, fetch the list of skills assigned for this course.
     * Trigger the skills to update the user points based on the upon completion option for this skill added in courses.
     *
     * @param int $userid
     * @param stdclass $eventdata
     * @return void
     */
    public function manage_course_completions(int $userid) {
        global $CFG;

        require_once($CFG->dirroot . '/lib/completionlib.php');

        $completion = new completion_info($this->get_course());
        $coursecompletion = $completion->is_course_complete($userid);
        // User completes the course, allocate the points for the levels for the enabled skills.
        if ($coursecompletion) {
            // Get course skills records.
            $skills = $this->get_instance_skills();
            foreach ($skills as $skillcourseid => $skill) {
                // Create a skill course record instance for this skill.
                $this->set_skill_instance($skillcourseid);
                $skill->assign_skills($this, $userid);
            }
        }
    }

    /**
     * Manage users completion.
     *
     * @return void
     */
    public function manage_users_completion() {
        global $CFG;

        require_once($CFG->dirroot . '/lib/enrollib.php');
        $context = \context_course::instance($this->courseid);

        // Enrolled users.
        $enrolledusers = get_enrolled_users($context);
        foreach ($enrolledusers as $user) {
            $this->manage_course_completions($user->id);
        }
    }

    /**
     * Remove the skills for this course award method.
     *
     * @param int $skillid
     * @return void
     */
    public static function remove_skills(int $skillid) {
        global $DB;

        $DB->delete_records('tool_skills_courses', ['skill' => $skillid]);
    }

    /**
     * Disable the skills assigned to courses.
     *
     * @param int $skillid
     * @return void
     */
    public static function disable_course_skills(int $skillid) {
        global $DB;

        // Get the list of skills.
        $courses = self::get_for_skill($skillid);
        foreach ($courses as $courseskillid => $course) {
            // Disable the skill of the course.
            $DB->update_record('tool_skills_courses', ['id' => $courseskillid, 'status' => 0]);
        }
    }

}
