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
 * Tool Skills - Log class to maintain the log of point allocation to users.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_skills;

/**
 * Maintain the user log of points allocations.
 */
class logs {

    /**
     * Log class isntance.
     *
     * @var \tool_skills\logs
     */
    protected static $instance;

    /**
     * Create the instance of the tool skills logs
     *
     * @return \tool_skills\logs
     */
    public static function get() {

        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Add the allocated user points and method of allocation to the logs.
     *
     * @param int $userid ID of the user earned the point
     * @param int $points Points the user earned, Contains negative points.
     * @param int $methodid Method id. ID of the table.
     * @param string $method Method of the allocation, Course and activity methods are available current now.
     * @param int $status Type of the points awarded. 1 for increase, 0 for negative points.
     * @return int ID of the logs inserted ID.
     */
    public function add(int $userid, int $points, int $methodid, string $method, int $status=1) {
        global $DB;

        $record = [
            'userid'      => $userid,
            'points'      => $points,
            'methodid'    => $methodid,
            'method'      => $method,
            'status'      => $status,
            'timecreated' => time()
        ];

        return $DB->insert_record('tool_skills_awardlogs', $record);
    }

    /**
     * Delete the user log of points allocation.
     *
     * @param int $userid ID of the user record.
     *
     * @return void
     */
    public function delete_user_log(int $userid) {
        global $DB;

        $DB->delete_records('tool_skills_awardlogs', ['userid' => $userid]);
    }

    /**
     * Deletes the log for the method.
     *
     * @param int $methodid
     * @param string $method
     * @return void
     */
    public function delete_method_log(int $methodid, string $method='course') {
        global $DB;

        $DB->delete_records('tool_skills_awardlogs', ['methodid' => $methodid, 'method' => $method]);
    }
}
