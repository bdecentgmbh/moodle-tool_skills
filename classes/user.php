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

    /**
     * Get the current user skills list.
     * TODO: Not used anymore.
     *
     * @return array
     */
    public function get_user_skills() {
        global $DB, $CFG;

        // Fetch the list of user enrolled courses.
        $courses = enrol_get_users_courses($this->userid, true, 'id');
        $ids = array_column($courses, 'id');

        // User not assigned to any course then not skills to earn.
        if (empty($ids)) {
            return [];
        }

        // Prepare IN condition query to get skills from the user enroled courses.
        list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'cid');

        $sql = "SELECT tscs.*, tscs.status as courseskillstatus, tscs.id as id, up.points AS userpoints
            FROM {tool_skills_courses} tscs
            JOIN {tool_skills} ts ON ts.id = tscs.skill
            LEFT JOIN {tool_skills_userpoints} up ON up.skill = ts.id AND up.userid = :userid
            WHERE tscs.status = :enabled AND ts.status = 1 AND ts.archived = 0 AND tscs.courseid $insql";

        $list = $DB->get_records_sql($sql, ['userid' => $this->userid, 'enabled' => 1] + $inparams);

        // Include list of course and skills.
        array_walk($list, function(&$point) {
            global $DB;
            // Skill record.
            $point->skillobj = skills::get($point->skill);
            // Skill courses.
            $point->skillcourse = courseskills::get($point->courseid);
            $point->skillcourse->set_skill_instance($point->id);

            // Extend addons to inlcude its skill data.
            \tool_skills\helper::extend_addons_add_userskills_data($point);

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

        if ($DB->delete_records('tool_skills_userpoints', ['userid' => $this->userid])) {
            $this->logs->delete_user_log($this->userid);
            return true;
        }
        return false;
    }

    /**
     * Get user points list.
     *
     * @param bool $withdata
     * @return array
     */
    public function get_user_points(bool $withdata=true) {
        global $DB;

        // Prepare the skills user points list.
        $sql = "SELECT * FROM {tool_skills_userpoints} WHERE userid=:userid";
        // Fetch the user points records.
        $list = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        // No need to fetch the data.
        if (!$withdata) {
            return $list;
        }

    }

    /**
     * Get the user points allocations for the method and methodid.
     *
     * @param string $method
     * @param int $methodid
     * @return void
     */
    public function get_user_award_by_method(string $method, int $methodid) {
        global $DB;

        if ($result = $DB->get_record('tool_skills_awardlogs',
            ['userid' => $this->userid, 'method' => $method, 'methodid' => $methodid])) {
            return $result->points;
        }

        return null;
    }

    /**
     * Get user proficiency level.
     *
     * @param int $skillid
     * @param int $points
     * @return string
     */
    public function get_user_proficency_level(int $skillid, int $points) {

        $skill = skills::get($skillid);
        $levels = $skill->get_levels();
        foreach ($levels as $level) {
            if ($points >= $level->points) {
                $proficiencylevel = $level->name;
            }
        }

        return $proficiencylevel ?? '';
    }

    /**
     * Get the user percentage in the skill.
     *
     * @param int $skillid Skill ID
     * @param int $points
     * @return string
     */
    public function get_user_percentage(int $skillid, $points) {
        $skillpoint = skills::get($skillid)->get_points_to_earnskill();
        $percentage = ($points / $skillpoint) * 100;
        return ((int) $percentage) . '%';
    }

}
