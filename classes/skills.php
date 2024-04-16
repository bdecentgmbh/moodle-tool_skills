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
 * Tool skills - Skills class to manage the skills allocation points, awards skills.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

defined('MOODLE_INTERNAL') || die();

use moodle_exception;
use stdClass;
use context_system;
use tool_skills\allocation_method;

require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/querylib.php');
require_once($CFG->dirroot.'/admin/tool/skills/lib.php');

/**
 * Skills manage instance, doing manage of skills tasks.
 */
class skills {

    /**
     * Reference of the status is enabled.
     *
     * @var bool
     */
    public const STATUS_ENABLE = 1;

    /**
     * Reference of the status is disabled.
     *
     * @var bool
     */
    public const STATUS_DISABLE = 0;

    /**
     * Represent the upon completion result is nothing.
     * @var int
     */
    public const COMPLETIONNOTHING = 0;

    /**
     * Represent the upon completion result is points.
     * @var int
     */
    public const COMPLETIONPOINTS = 1;

    /**
     * Represent the upon completion result is setlevel.
     * @var int
     */
    public const COMPLETIONSETLEVEL = 2;

    /**
     * Represent the upon completion result is forcelevel.
     * @var int
     */
    public const COMPLETIONFORCELEVEL = 3;

    /**
     * Represent the upon completion result is points grade achieve in the activity.
     * @var int
     */
    public const COMPLETIONPOINTSGRADE = 4;

    /**
     * Skill instance id.
     *
     * @var int
     */
    protected $skillid;

    /**
     * Skill db record.
     *
     * @var stdClass
     */
    protected $skillrecord;

    /**
     * Data updated structure data of the skill.
     *
     * @var stdClass
     */
    protected $data;

    /**
     * Data updated structure data of the levels associated with this skill.
     *
     * @var array
     */
    protected $levels;

    /**
     * Log the skill points allocation to users.
     *
     * @var \tool_skills\logs
     */
    protected $log;

    /**
     * Generate the skill instance for the skill id.
     *
     * @param int $skillid
     */
    protected function __construct(int $skillid) {
        // Set the skill id for this instance.
        $this->skillid = $skillid;
        // Generate the skill record.
        $this->skillrecord = $this->fetch_skill_record() ?? new stdClass;

        $this->data = $this->update_data_structure();

        // Create logs instance.
        $this->log = new \tool_skills\logs();
    }

    /**
     * Fetch the current skill record without any data structure update.
     *
     * @return stdClass Skill record.
     */
    public function get_skill_record(): stdClass {
        return $this->skillrecord;
    }

    /**
     * Fetch the current skill data with updated data structures.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->data->id;
    }

    /**
     * Fetch the current skill data with updated data structures.
     *
     * @return stdClass
     */
    public function get_data(): stdClass {
        return $this->data;
    }

    /**
     * Get the return name.
     *
     * @return string
     */
    public function get_name(): string {
        return format_string($this->data->name);
    }

    /**
     * Fetch the skill record from db for the current skillid.
     *
     * @return stdClass|bool
     */
    protected function fetch_skill_record(): ?stdClass {
        global $DB;

        if ($skill = $DB->get_record('tool_skills', ['id' => $this->skillid])) {
            return $skill;
        } else {
            throw new moodle_exception('skillnotfound', 'tool_skills');
        }

        return false;
    }

    /**
     * Update the strucutre of the skill record.
     *
     * @return stdClass
     */
    protected function update_data_structure(): stdClass {

        // Clone the skill record.
        $data = clone $this->skillrecord;
        // Decode the categories.
        $data->categories = $data->categories ? json_decode($data->categories) : [];

        $data->levels = array_values(array_map(fn($r) => (array) $r, $this->get_levels()));

        $levelscount = count($data->levels);
        $data->levelsrecordscount = $levelscount;
        $data->levelscount = $levelscount ? $levelscount - 1 : 0; // Levels count without the default level 0.

        return $data;
    }

    /**
     * Updates the "status" field of the current skill.
     *
     * @param bool $status The new value for the "status" field.
     * @return bool True if the update was successful, false otherwise.
     */
    public function update_status(bool $status) {

        return $this->update_field('status', $status);
    }

    /**
     * Updates a field of the current skill with the given key and value.
     *
     * @param string $key The key of the field to update.
     * @param mixed $value The new value of the field.
     * @return bool|int Returns true on success, or false on failure.
     */
    public function update_field($key, $value) {
        global $DB;

        $result = $DB->set_field('tool_skills', $key, $value, ['id' => $this->skillid]);

        return $result;
    }

    /**
     * Delete the current skill and all its associated levels from the database.
     *
     * @return bool True if the deletion is successful, false otherwise.
     */
    public function delete_skill() {
        global $DB;

        if ($DB->delete_records('tool_skills', ['id' => $this->skillid])) {

            // Remove all the levels associated with this skill.
            \tool_skills\level::remove_skill_levels($this->skillid);
            // Delete all its actions.
            \tool_skills\courseskills::remove_skills($this->skillid);
            // Extend the addons remove skills.
            \tool_skills\helper::extend_addons_remove_skills($this->skillid);
            // Remove the user points for the skill.
            $DB->delete_records('tool_skills_userpoints', ['skill' => $this->skillid]);
            // Remove skills points award logs.
            $DB->delete_records('tool_skills_awardlogs', ['skill' => $this->skillid]);

            return true;
        }
        return false;
    }


    /**
     * Archive the skills
     *
     * @return void
     */
    public function archive_skill() {
        global $DB;

        if ($DB->update_record('tool_skills', ['id' => $this->skillid, 'archived' => 1, 'timearchived' => time()])) {

            \tool_skills\courseskills::disable_course_skills($this->skillid);
        }
    }

    /**
     * Activate the skills.
     *
     * @return void
     */
    public function active_skill() {
        global $DB;

        $DB->update_record('tool_skills', ['id' => $this->skillid, 'archived' => 0, 'timearchived' => null]);
    }

    /**
     * Duplicate the skill and its levels.
     *
     * @return bool
     */
    public function duplicate() {
        return true;
    }

    /**
     * Find how many points user should earn to complete this skill.
     *
     * @return int
     */
    public function get_points_to_earnskill() {

        $levels = $this->get_levels(); // List of levels, available in the skill.

        if (!empty($levels)) {
            $levelpoints = array_column($levels, 'points');
            $pointstocomplete = max($levelpoints);
        }

        return $pointstocomplete ?? 0;
    }

    /**
     * Fetch the levels available for this skills.
     *
     * @return void
     */
    protected function fetch_levels(): void {
        global $DB;

        $this->levels = $DB->get_records('tool_skills_levels', ['skill' => $this->skillid]);
    }

    /**
     * Get the count of the levels for this skill.
     *
     * @return array
     */
    public function get_levels(): array {

        if (empty($this->levels)) {
            $this->fetch_levels();
        }

        return $this->levels;
    }

    /**
     * Get the count of the levels for this skill.
     *
     * @return int
     */
    public function get_levels_count(): int {
        global $DB;

        return count($this->get_levels());
    }

    /**
     * Fetch the user skill points table record.
     *
     * @param int $userid
     * @param bool $create
     *
     * @return stdClass
     */
    public function get_user_skill(int $userid, $create=true) {
        global $DB;

        // Fetch the user skill record.
        $condition = ['userid' => $userid, 'skill' => $this->skillid];
        $userskill = $DB->get_record('tool_skills_userpoints', $condition);

        if (empty($userskill) && $create) {

            $record = $condition;
            $record['points'] = 0;
            $record['timecreated'] = time();

            $DB->insert_record('tool_skills_userpoints', $record);

            return $this->get_user_skill($userid, false); // Don't need to create again.
        }

        return $userskill;
    }

    /**
     * Force user points to match this level.
     *
     * @param allocation_method $skillobj
     * @param int $levelid
     * @param int $userid
     *
     * @return void
     */
    public function force_level($skillobj, int $levelid, int $userid) {

        // Fetch the level instance for this level.
        $level = level::get($levelid);
        $levelpoints = $level->get_data()->points; // Points to complete this level.
        // Update the new points for this user in db.
        $this->set_userskill_points($userid, $levelpoints);
        // Create a award log for this user point increase.
        $this->create_user_point_award($skillobj, $userid, $levelpoints);
    }

    /**
     * Increase the user points to complete this level.
     *
     * @param allocation_method $skillobj
     * @param int $levelid
     * @param int $userid
     *
     * @return void
     */
    public function moveto_level($skillobj, int $levelid, int $userid) {
        // User skill.
        $userskill = $this->get_user_skill($userid);
        // Fetch the level instance for this level.
        $level = level::get($levelid);
        $levelpoints = $level->get_data()->points; // Points to complete this level.

        // User not reached this level, then increase the users skill points to reach the new level.
        if ($userskill->points < $levelpoints) {
            // Update the new points for this user in db.
            $this->set_userskill_points($userid, $levelpoints);
            // Create a award log for this user point increase.
            $this->create_user_point_award($skillobj, $userid, $levelpoints - $userskill->points);
        }
    }

    /**
     * Increase the points.
     *
     * @param allocation_method $skillobj
     * @param int $points
     * @param int $userid
     *
     * @return void
     */
    public function increase_points($skillobj, int $points, int $userid) {
        // Get user skill current record, create new one if not found.
        $userskill = $this->get_user_skill($userid);
        // Increase the allocated points with current user points.
        $levelpoints = $userskill->points + $points;

        // Update the new points for this user in db.
        $this->set_userskill_points($userid, $levelpoints);

        // Create a award log for this user point increase.
        $this->create_user_point_award($skillobj, $userid, $points);
    }

    /**
     * Update the course completion points to users
     *
     * @param int $userid
     * @param int $points
     * @return int
     */
    public function set_userskill_points(int $userid, int $points): int {
        global $DB;

        $record = ['skill' => $this->skillid, 'userid' => $userid];

        if ($data = $DB->get_record('tool_skills_userpoints', $record)) {
            $id = $data->id;
            $data->points = $points;
            $data->timemodified = time();

            $DB->update_record('tool_skills_userpoints', $data);

        } else {
            $record['points'] = $points;
            $record['timecreated'] = time();

            $id = $DB->insert_record('tool_skills_userpoints', $record);
        }

        return $id;
    }

    /**
     * Create a log for the user point allocation.
     *
     * @param allocation_method $skillobj
     * @param int $userid
     * @param int $points
     * @return void
     */
    public function create_user_point_award($skillobj, int $userid, int $points) {

        // Find the method of the course skills.
        if ($skillobj instanceof \tool_skills\courseskills) {
            $method = 'course';
        } else {
            $method = helper::extend_addons_get_allocation_method($skillobj);
        }

        // Allocation method id.
        $methodid = $skillobj->get_data()->id;

        // Log the point awarded to users and the method.
        $this->log->add($this->skillid, $userid, $points, $methodid, $method);
    }

    /**
     * Get the list of proficient users in this skill.
     *
     * @return array
     */
    public function get_proficient() {
        global $DB;

        // Points to earn this skill.
        $proficientpoint = $this->get_points_to_earnskill();
        // User points.
        $userpoints = $DB->get_records('tool_skills_userpoints', ['skill' => $this->skillid]);
        $list = [];
        foreach ($userpoints as $id => $points) {
            // Verify the user is reached the maximum points for the level.
            if ($points->points >= $proficientpoint) {
                $list[] = $points->userid; // This user is proficient in this skill.
            }
        }
        // Return the list of proficient users id.
        return $list;
    }

    /**
     * Generate the skill instance for the skill id.
     *
     * @param int $skillid
     */
    public static function get(int $skillid): \tool_skills\skills {
        // Create the instance for this skill and return.
        return new self($skillid);
    }

    /**
     * Manage the skill add form submitted data. Create new instance or update the existing instance.
     * Create levels and update existing levels, delete removed levels.
     *
     * @param stdClass $formdata
     * @return void
     */
    public static function manage_instance($formdata) {
        global $DB, $PAGE;

        // Verfiy the current user has capability to manage skills.
        require_capability('tool/skills:manage', context_system::instance());

        $record = clone $formdata;
        $record->categories = json_encode($record->categories);

        // Start the database transaction.
        $transaction = $DB->start_delegated_transaction();

        if (isset($formdata->id) && $formdata->id != '' && $DB->record_exists('tool_skills', ['id' => $formdata->id])) {
            // ID of the modified skill.
            $skillid = $formdata->id;
            // Verify the identity key is exists.
            $identitysql = 'SELECT * FROM {tool_skills} WHERE identitykey =:identitykey AND id <> :skillid';

            // Record exists then stop inserting and redirect with error message.
            if ($DB->record_exists_sql($identitysql, ['skillid' => $skillid, 'identitykey' => $record->identitykey])) {
                // Redirect to the current page with error message.
                redirect($PAGE->url, get_string('error:identityexists', 'tool_skills'));
            }
            // Time modified the skill.
            $record->timemodified = time();

            // Update the skill record.
            $DB->update_record('tool_skills', $record);

        } else {
            // New record add the created time.
            $record->timecreated = time();

            // Record exists then stop inserting and redirect with error message.
            if ($DB->record_exists('tool_skills', ['identitykey' => $record->identitykey])) {
                // Redirect to the current page with error message.
                redirect($PAGE->url, get_string('error:identityexists', 'tool_skills'));
            }

            // Insert the record of the new skill.
            $skillid = $DB->insert_record('tool_skills', $record);
        }

        if (isset($formdata->levelscount)) {
            // Get the updated skill instance.
            $skill = self::get($skillid);

            \tool_skills\level::manage_level_instance($skill, $formdata->levels);
        }

        // Allow the query changes to the DB.
        $transaction->allow_commit();

        return $skillid ?? false;
    }

}
