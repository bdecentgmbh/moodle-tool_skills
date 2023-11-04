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
 * Tool skills - Skills interface.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use stdClass;

/**
 * Abstract class to define skill points allocation methods, like course and activity.
 */
abstract class allocation_method {

    /**
     * Contains the skill instance data for this allocation method.
     *
     * @var stdClass|null
     */
    protected $data;

    /**
     * Log the skill points allocation to users.
     *
     * @var \tool_skills\logs
     */
    protected $logs;

    /**
     * Instance ID of the allocation method.
     *
     * @var int
     */
    protected $instanceid;

    /**
     * Constructor
     */
    protected function __construct() {
        $this->data = new stdClass();
    }

    /**
     * Fetch the skills assigned/enabled for this allocation method.
     *
     * @return array
     */
    abstract public function get_instance_skills(): array;

    /**
     * Remove the skills records assigned for this allocation method.
     *
     * @return void
     */
    abstract public function remove_instance_skills();

    /**
     * Get the record for the allocation method for the instance skill, Set the skill in the instance.
     *
     * @return stdclass
     */
    abstract protected function build_data();

    /**
     * Remove this allocation method for this skillid.
     *
     * @param int $skillid
     * @return void
     */
    abstract public static function remove_skills(int $skillid);

    /**
     * Create a instance of skill for the given allocation method id.
     *
     * @param int $instanceid ID of the allocation method record
     * @return void
     */
    public function set_skill_instance(int $instanceid) {

        $this->instanceid = $instanceid;
    }

    /**
     * Return the data of skill instance for this allocation method.
     *
     * @return stdClass|null
     */
    public function get_data() {

        // Verify the data is build or empty.
        if (empty($this->data) || $this->data == new stdClass()) {
            $this->build_data();
        }
        // Return the data.
        return $this->data;
    }

    /**
     * Get skill log class instance to store user point allocations.
     *
     * @return \tool_skills\logs
     */
    public function get_logs() : \tool_skills\logs {

        if ($this->logs == null) {
            $this->logs = new \tool_skills\logs();
        }

        return $this->logs;
    }
}
