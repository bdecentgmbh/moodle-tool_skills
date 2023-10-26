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
 * Tool Skills - Assign skills and points to users.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Assign the skills and points to users.
 */
class user {

    /**
     * User class instance object.
     *
     * @var self
     */
    protected static $instance;

    /**
     * ID of the user.
     *
     * @var int
     */
    protected $userid;

    /**
     * Tool skills main instance.
     *
     * @var \tool_skills\skills
     */
    protected $skills;

    /**
     * Skills log instance, to store point allocation.
     *
     * @var \tool_skills\logs
     */
    protected $logs;

    /**
     * Create the user skills instance for the given userid.
     *
     * @param int $userid
     * @return \tool_skills\user
     */
    public static function get($userid) {

        if (self::$instance == null || self::$instance->get_userid() == $userid) {
            self::$instance = new self($userid);
        }

        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @param int $userid User ID.
     */
    protected function __construct(int $userid) {
        $this->userid = $userid;
        // $this->skills = $this->get_skill_courses();
        $this->logs = new \tool_skills\logs();
    }

    /**
     * Fetch the instance user id.
     *
     * @return int
     */
    public function get_userid() {
        return $this->userid;
    }

    /* protected function get_skill_courses() {
        global $DB;

        // Fetch the list of user enrolled courses.
        $courses = enrol_get_users_courses($this->userid, true, 'id');
        $ids = array_column($courses, 'id');

        // Prepare IN condition query to get skills from the user enroled courses.
        list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'cid');

        $sql = "SELECT
            FROM {tool_skills_courseskills} tscs
            JOIN {tool_skills} ts ON ts.id = tscs.skill
            JOIN (
                SELECT * FROM {tool_skills_userpoints} WHERE userid=:userid
            ) tsup ON tsup.skill = tscs.skill
            WHERE tscs.status = :enabled AND tscs.courseid $insql
        ";

        $list = $DB->get_records_sql($sql, ['userid' => $this->userid, 'enabled' => 1] + $inparams);
    } */

    /**
     * Get the current user skills list.
     * TODO: Not used anymore.
     *
     * @param bool $awarded Fetch the list with awarded list.
     * @return array
     */
    public function get_user_skills() {
        global $DB;

        // Fetch the list of user enrolled courses.
        $courses = enrol_get_users_courses($this->userid, true, 'id');
        $ids = array_column($courses, 'id');
        // Prepare IN condition query to get skills from the user enroled courses.
        list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'cid');

        $sql = "SELECT tscs.*, tscs.status as courseskillstatus, tscs.id as id
            FROM {tool_skills_courses} tscs
            JOIN {tool_skills} ts ON ts.id = tscs.skill
            WHERE tscs.status = :enabled AND tscs.courseid $insql";

        $list = $DB->get_records_sql($sql, ['userid' => $this->userid, 'enabled' => 1] + $inparams);

        // Include list of course and skills.
        array_walk($list, function(&$point) {
            global $DB;
            // Skill record.
            $point->skillobj = skills::get($point->skill);
            // Skill courses.
            $point->skillcourse = courseskills::get($point->courseid);

            $point->userpoints = $DB->get_record('tool_skills_userpoints', ['skill' => $point->skill, 'userid' => $this->userid]);
            // Skill levels.
            $point->levels = $DB->get_records('tool_skills_levels', ['skill' => $point->skill]);
        });

        return $list;
    }

    /**
     * Remove the user skills points list.
     *
     * @return void
     */
    public function remove_user_skillpoints() {
        global $DB;

        // Fetch the list of user points record withdata skill data.
        $skills = $this->get_user_points(false);
        // List of skill ids.
        $skillids = array_column($skills, 'id');
        // Delete user points.
        $DB->delete_records('tool_skills_userpoints', ['id' => $skillids]);
        // Delete the user points log.
        $this->logs->delete_user_log($this->userid);
    }

    /**
     * Get user points.
     *
     * @return array
     */
    public function get_user_points(bool $withdata=true) {
        global $DB;

        // Fetch the list of user enrolled courses.
        /* $courses = enrol_get_users_courses($this->userid, true, 'id');
        $ids = array_column($courses, 'id');
        // Prepare IN condition query to get skills from the user enroled courses.
        list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'cid'); */

        // Prepare the skills user points list.
        $sql = "SELECT * FROM {tool_skills_userpoints} WHERE userid=:userid";
        // Fetch the user points records.
        $list = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        // No need to fetch the data.
        if (!$withdata) {
            return $list;
        }

        /* // Include list of course and skills.
        array_walk($list, function(&$point) {
            global $DB;
            // Skill record.
            $point->skillobj = skills::get($point->skill);
            // Skill courses.
            $point->skillcourses = courseskills::get_for_skill($point->skill);
            // Skill levels.
            $point->levels = $DB->get_records('tool_skills_levels', ['skill' => $point->skill]);
        });

        print_object($list);exit;

        return $list; */
    }


}
