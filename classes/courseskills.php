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

defined('MOODLE_INTERNAL') || die();

use completion_info;
use moodle_exception;
use stdClass;

require_once($CFG->dirroot.'/admin/tool/skills/lib.php');

/**
 * Manage the skills for courses. Trigger skills to assign point for users.
 */
class courseskills extends \tool_skills\allocation_method {

    /**
     * ID of the course skill record id.
     *
     * @var int
     */
    protected $id;

    /**
     * ID of the course record id.
     *
     * @var int
     */
    protected $courseid;

    /**
     * Constructor
     *
     * @param int $courseid ID of the skill course record.
     */
    protected function __construct(int $courseid) {
        parent::__construct(); // Create a parent instance
        // Course id.
        $this->courseid = $courseid;

    }

    /**
     * Create the retun the clas instance for this skillcourse id.
     *
     * @param int $courseid
     * @return self
     */
    public static function get(int $courseid): self {
        return new self($courseid);
    }

    /**
     * Fetch to the skills course data .
     *
     * @param int $skillid
     * @return self
     */
    public static function get_for_skill(int $skillid): array {
        global $DB;

        $courses = $DB->get_records('tool_skills_courses', ['skill' => $skillid]);

        return array_map(fn($course) => new self($course->id), $courses);
    }

    /**
     * Get the course record for this courseid.
     *
     * @return stdClass Course record data.
     */
    public function get_course(): stdClass {
        return get_course($this->courseid);
    }

    /**
     * Fetch the skills assigned/enabled for this course.
     *
     * @param int $skillid Id of the skill.
     * @return array
     */
    public function get_instance_skills($skillid=null): array {
        global $DB;

        $condition = ['courseid' => $this->courseid, 'status' => 1];
        if ($skillid !== null) {
            $condition['skill'] = $skillid;
        }

        $skills = $DB->get_records('tool_skills_courses', $condition);

        return array_map(fn($sk) => skills::get($sk->skill), $skills);
    }

    /**
     * Fetch the skills assigned/enabled for this course.
     *
     * @param int $skillid Id of the skill.
     * @return array
     */
    public function get_instance_disabled_skills($skillid=null): array {
        global $DB;

        $condition = ['courseid' => $this->courseid, 'status' => 0];
        if ($skillid !== null) {
            $condition['skill'] = $skillid;
        }

        $skills = $DB->get_records('tool_skills_courses', $condition);

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

        \tool_skills\helper::extend_addons_remove_course_instance($this->courseid);

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
    public function get_points() {

        $this->build_data(); // Build the data of the skill for this course.

        return $this->data->points ?? false;
    }

    /**
     * Get points earned from this course completion.
     *
     * @return string
     */
    public function get_points_earned_fromcourse() {

        $data = $this->get_data();

        if ($data->uponcompletion == skills::COMPLETIONPOINTS) {
            return $data->points;
        } else if ($data->uponcompletion == skills::COMPLETIONFORCELEVEL || $data->uponcompletion == skills::COMPLETIONSETLEVEL) {
            $levelid = $data->level;
            $level = \tool_skills\level::get($levelid);
            return $level->get_points();
        }

        return '';
    }

    /**
     * Fetch the points user earned for this instance.
     *
     * @param int $userid
     * @return int
     */
    public function get_user_earned_points(int $userid) {

        $user = \tool_skills\user::get($userid);
        $points = $user->get_user_award_by_method('course', $this->instanceid);

        return $points ?? null;
    }

    /**
     * Manage the course completions to allocate the points to the courses skills.
     *
     * Given course is completed for this user, fetch the list of skills assigned for this course.
     * Trigger the skills to update the user points based on the upon completion option for this skill added in courses.
     *
     * @param int $userid
     * @param array $skills
     * @param bool|null $status
     * @return void
     */
    public function manage_course_completions(int $userid, array $skills=[], bool $status=null) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/completionlib.php');

        $completion = new completion_info($this->get_course());
        $coursecompletion = $completion->is_course_complete($userid);
        // User completes the course, allocate the points for the levels for the enabled skills.
        if ($coursecompletion) {
            // Get course skills records.
            if ($status === null || $status == 1) {
                $skills = $skills ?: $this->get_instance_skills();
                foreach ($skills as $skillcourseid => $skill) {
                    $this->manage_user_skill_points($skill, $userid, $skillcourseid);
                }
            }

            // Disable user points award if the instance is disabled.
            $disabledskills = $this->get_instance_disabled_skills();
            if ($status !== null && $status === 0 && $skills) {
                $disabledskills = $skills;
            }

            if (!empty($disabledskills)) {
                foreach ($disabledskills as $skillcourseid => $skill) {
                    $this->disable_user_skill_points($skill, $userid, $skillcourseid);
                }
            }
        }
    }

    /**
     * Manage the points award to the user for a skill.
     *
     * @param tool_skills\skills $skill
     * @param int $userid
     * @param int $skillcourseid
     *
     * @return void
     */
    protected function manage_user_skill_points($skill, $userid, $skillcourseid) {
        global $DB;

        // Create a skill course record instance for this skill.
        $this->set_skill_instance($skillcourseid);
        // Get the data.
        $csdata = $this->build_data();
        // Start the database transaction.
        $transaction = $DB->start_delegated_transaction();
        $updateskill = true; // Update the course skills until it doesn't already awarded.

        if ($record = $DB->get_record('tool_skills_awardlogs', ['userid' => $userid, 'skill' => $csdata->skill,
            'methodid' => $csdata->id, 'method' => 'course',
            ])) {

            // Course points user will earned upon the course completion.
            $coursepoints = $this->get_points_earned_fromcourse();
            $currentpoints = $record->points; // Previous points user earned stored in the log.
            $updateskill = false; // No need to update the points until the course skill is updated in its points.

            if ($coursepoints != $currentpoints) {

                $updateskill = true; // Verified the course skill points updated, then update the user points.
                $skillpoint = $skill->get_user_skill($userid)->points;
                $skillpoint -= $currentpoints; // Remove the previously awarded course skill points.

                // Update the skill point for the user.
                $skill->set_userskill_points($userid, $skillpoint);
            }
        }

        if ($updateskill) {

            switch ($csdata->uponcompletion) {

                case skills::COMPLETIONFORCELEVEL:
                    $skill->force_level($this, $csdata->level, $userid);
                    break;

                case skills::COMPLETIONSETLEVEL:
                    $skill->moveto_level($this, $csdata->level, $userid);
                    break;

                case skills::COMPLETIONPOINTS:
                    $skill->increase_points($this, $csdata->points, $userid);
                    break;

                case skills::COMPLETIONNOTHING:
                    $skill->create_user_point_award($this, $userid, 0);
                    break;
            }
        }
        // End the database transaction.
        $transaction->allow_commit();
    }

    /**
     * Remove the user earned points for this course from the skill points.
     *
     * @param tool_skills\skills $skill
     * @param int $userid
     * @param int $skillcourseid Course skill instance id (skill_courses table id).
     * @return void
     */
    protected function disable_user_skill_points($skill, $userid, $skillcourseid) {
        global $DB;

        // Create a skill course record instance for this skill.
        $this->set_skill_instance($skillcourseid);
        // Get the data.
        $csdata = $this->build_data();
        // Start the database transaction.
        $transaction = $DB->start_delegated_transaction();

        if ($record = $DB->get_record('tool_skills_awardlogs', ['userid' => $userid, 'skill' => $csdata->skill,
            'methodid' => $csdata->id, 'method' => 'course'])) {

            $currentpoints = $record->points; // Previous points user earned stored in the log.
            $skillpoint = $skill->get_user_skill($userid)->points;
            $skillpoint -= $currentpoints; // Remove the previously awarded course skill points.
            // Update the skill point for the user.
            $skill->set_userskill_points($userid, $skillpoint);
            // Update the log of points earned from this instance to 0.
            $skill->create_user_point_award($this, $userid, 0);
        }

        // End the database transaction.
        $transaction->allow_commit();

    }

    /**
     * Manage the user completion actions, award or reduce the updated/completed courses result for the enrolled users.
     *
     * @param int $skillid
     * @param bool $status
     *
     * @return void
     */
    public function manage_users_completion(int $skillid=null, bool $status=null) {
        global $CFG;

        require_once($CFG->dirroot . '/lib/enrollib.php');
        $context = \context_course::instance($this->courseid);

        $skills = [];
        if ($skillid) {
            $skills = $this->get_instance_skills($skillid); // Fetch the enabled skills.
        }

        // If the given skill is not enabled, then fetch the disabled skills.
        if ($skillid && empty($skills)) {
            $skills = $this->get_instance_disabled_skills($skillid);
            $status = 0;
        }

        // Enrolled users.
        $enrolledusers = get_enrolled_users($context);
        foreach ($enrolledusers as $user) {
            $this->manage_course_completions($user->id, $skills, $status);
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
