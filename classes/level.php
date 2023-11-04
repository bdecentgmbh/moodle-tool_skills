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
 * Tool skills - Manage levels for the skills.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use moodle_exception;
use stdClass;
use context_system;
use html_writer;
use moodle_url;

/**
 * Level manage instance, fetch points and other data.
 */
class level extends skills {


    /**
     * Level instance id.
     *
     * @var int
     */
    protected $levelid;

    /**
     * level db record.
     *
     * @var stdClass
     */
    protected $levelrecord;

    /**
     * Data updated structure data of the level.
     *
     * @var stdClass
     */
    protected $data;

    /**
     * Skill instance id.
     *
     * @var int
     */
    protected $skillid;

    /**
     * Data updated structure data of the skill.
     *
     * @var \tool_skills\skills
     */
    protected $skill;

    /**
     * Generate the level instance for the level id.
     *
     * @param int $levelid
     */
    protected function __construct(int $levelid) {
        // Set the skill id for this instance.
        $this->levelid = $levelid;
        // Generate the skill record.
        $this->levelrecord = $this->fetch_record() ?? new stdClass;

        $this->data = $this->levelrecord;

        $this->skillid = $this->levelrecord->skill;

        $this->skill = skills::get($this->skillid);
    }

    /**
     * Fetch the current level record without any data structure update.
     *
     * @return stdClass Level record.
     */
    public function get_level_record() : stdClass {
        return $this->levelrecord;
    }

    /**
     * Fetch the current level data with updated data structures.
     *
     * @return stdClass
     */
    public function get_data() : stdClass {
        return $this->data;
    }

    /**
     * Fetch the skill record from db for the current skillid.
     *
     * @return stdClass|bool
     */
    protected function fetch_record() : ?stdClass {
        global $DB;

        if ($skill = $DB->get_record('tool_skills_levels', ['id' => $this->levelid])) {
            return $skill;
        } else {
            throw new moodle_exception('levelnotfound', 'tool_skills');
        }

        return false;
    }

    /**
     * Delete the current skill and all its associated levels from the database.
     *
     * @return bool True if the deletion is successful, false otherwise.
     */
    public function delete_level() {
        global $DB;

        if ($DB->delete_records('tool_skills_level', ['id' => $this->levelid])) {
            return true;
        }
        return false;
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
     * Get the points of the level.
     *
     * @return int
     */
    public function get_points() {
        return $this->data->points;
    }

    /**
     * Generate the skill instance for the skill id.
     *
     * @param int $levelid
     */
    public static function get(int $levelid) : \tool_skills\level {
        // Create the instance for this skill and return.
        return new self($levelid);
    }

    /**
     * Manage the level insert and update from the skill add form submitted data.
     * Create levels and update existing levels, delete removed levels.
     *
     * @param \tool_skills\skills $skill
     * @param array $levels
     * @return array List of levels created for this skills.
     */
    public static function manage_level_instance(\tool_skills\skills $skill, array $levels) {
        global $DB, $PAGE;

        // Verfiy the current user has capability to manage skills.
        require_capability('tool/skills:manage', context_system::instance());

        // No levels to update.
        if (empty($levels)) {
            return false;
        }

        // Currently available levels list.
        $currentlevels = array_column($skill->get_levels(), 'id');

        // Start the database transaction.
        $transaction = $DB->start_delegated_transaction();

        foreach ($levels as $num => $level) {

            $level = (object) $level; // Convert to stdClass.

            $level->skill = $skill->get_id();

            if (isset($level->id) && $level->id != "" && $DB->record_exists('tool_skills_levels', ['id' => $level->id])) {
                // Level id to update.
                $levelid = $level->id;
                // Time modified the level.
                $level->timemodified = time();
                // Update the level record.
                $DB->update_record('tool_skills_levels', $level);
            } else {
                // New record add the created time.
                $level->timecreated = time();
                // Insert the record of the new skill.
                $levelid = $DB->insert_record('tool_skills_levels', $level);
            }

            $levelslist[] = $levelid;
        }

        // Delete the removed levels.
        if (isset($levelslist) && !empty($currentlevels)) {
            $removeids = array_diff($currentlevels, $levelslist);
            $DB->delete_records_list('tool_skills_levels', 'id', $removeids);
        }

        // Allow the query changes to the DB.
        $transaction->allow_commit();

        return $levelslist ?? [];
    }

    /**
     * Delete levels for this skills.
     *
     * @param int $skillid
     *
     * @return void
     */
    public static function remove_skill_levels(int $skillid) {
        global $DB;

        $DB->delete_records('tool_skills_levels', ['skill' => $skillid]);
    }

}
