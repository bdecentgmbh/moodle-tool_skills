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
 * Tool skills - Event observer.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\events;

use tool_skills\courseskills;
use tool_skills\skills;
use tool_skills\user;

/**
 * Tool skills event observer to handler multiple triggered events.
 */
class observer {

    /**
     * Observe the course completion event and update the assigned skills of this course for this user.
     *
     * @param \core\event\course_completed $event
     * @return void
     */
    public static function course_completed(\core\event\course_completed $event) {
        // Fetch the event data.
        $data = $event->get_data();
        // ID of the completed course.
        $courseid = $data['courseid'];
        $relateduserid = $data['relateduserid']; // Completed user id.

        // Manage the upon completion options for various skills assigned in this course.
        courseskills::get($courseid)->manage_course_completions($relateduserid, [], null);
    }

    /**
     * User deleted, then remove the user points records.
     *
     * @param \core\event\user_deleted $event
     * @return void
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        // Fetch the event data.
        $data = $event->get_data();
        $relateduserid = $data['objectid']; // Completed user id.
        // Remove the user skill points.
        user::get($relateduserid)->remove_user_skillpoints();
    }

    /**
     * Course deleted, then deletes the course skills records.
     *
     * @param \core\event\course_deleted $event
     * @return void
     */
    public static function course_deleted(\core\event\course_deleted $event) {

        // Fetch the event data.
        $data = $event->get_data();
        // ID of the completed course.
        $courseid = $data['courseid'];
        // Remove the course skills of the deleted course.
        courseskills::get($courseid)->remove_instance_skills($courseid);
    }
}
