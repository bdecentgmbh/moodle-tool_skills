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
 * Privacy implementation for skills admin tool
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_skills\privacy;

use stdClass;
use context;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * The skills stores user points and allocation logs details.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * List of used data fields summary meta key.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        // User points table fields meta summary.
        $userpointsmetadata = [
            'userid' => 'privacy:metadata:userpoints:userid',
            'skill' => 'privacy:metadata:userpoints:skill',
            'points' => 'privacy:metadata:userpoints:points',
            'timecreated' => 'privacy:metadata:userpoints:timecreated',
            'timemodified' => 'privacy:metadata:userpoints:timemodified',
        ];
        $collection->add_database_table('tool_skills_userpoints', $userpointsmetadata, 'privacy:metadata:userpoints');

        // User points allocation awards log data.
        $awardlogsmetadata = [
            'userid' => 'privacy:metadata:awardlogs:userid',
            'points' => 'privacy:metadata:awardlogs:points',
            'methodid' => 'privacy:metadata:awardlogs:methodid',
            'method' => 'privacy:metadata:awardlogs:method',
            'timecreated' => 'privacy:metadata:awardlogs:timecreated',
        ];
        $collection->add_database_table('tool_skills_awardlogs', $awardlogsmetadata, 'privacy:metadata:awardlogs');

        // Added moodle subsystems used in tool skills.
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:userpointsexplanation');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param  int         $userid    The user to search.
     * @return contextlist $contextlist The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        // User completions.
        $sql = "SELECT ctx.id
                FROM {context} ctx
                INNER JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                INNER JOIN {tool_skills_courses} tsc ON c.id = tsc.courseid
                INNER JOIN {tool_skills_awardlogs} tsl ON tsl.method = 'course' AND tsc.id = tsl.methodid
                INNER JOIN {tool_skills_userpoints} up ON up.skill = tsc.skill
                INNER JOIN {tool_skills} ts ON ts.id = up.skill
                WHERE tsl.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        $params = [
            'instanceid' => $context->instanceid,
        ];

        // Awarded points.
        $sql = "SELECT tsl.userid
        FROM {course} c
        JOIN {tool_skills_courses} tsc ON tsc.courseid = c.id
        JOIN {tool_skills_awardlogs} tsl ON tsl.method = 'course' AND tsl.methodid = tsc.id
        WHERE c.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $course = $DB->get_record('course', ['id' => $context->instanceid]);

        if (empty($userlist->count())) {
            return false;
        }

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        $params = $userinparams;
        $sql = " userid {$userinsql} ";

        $DB->delete_records_select('tool_skills_awardlogs', $sql, $params);
        $DB->delete_records_select('tool_skills_userpoints', $sql, $params);

    }

    /**
     * Delete user completion data for multiple context.
     *
     * @param approved_contextlist $contextlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $DB->delete_records('tool_skills_awardlogs', ['userid' => $userid]);
            $DB->delete_records('tool_skills_userpoints', ['userid' => $userid]);
        }
    }

    /**
     * Delete all completion data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        // Get the course.
        $course = get_course($context->instanceid);
        if (!$course) {
            return;
        }

        $courses = $DB->get_records('tool_skills_courses', ['courseid' => $course->id]);
        foreach ($courses as $skillcourse) {
            $log = $DB->get_record('tool_skills_awardlogs', ['methodid' => $skillcourse->id, 'method' => 'course']);
            $points = $DB->get_record('tool_skill_userpoints', ['skill' => $log->skill, 'userid' => $log->userid]);

            // Find and remove the points awarded for this user from this course.
            $point = $points->points - $log->point;
            $DB->set_field('tool_skill_userpoints', 'points', $point, ['id' => $points->id]);

            (new \tool_skills\logs())->delete_method_log($course->id, 'course'); // Remove the log.
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        // Context user.
        $user = $contextlist->get_user();
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT *, up.id AS id, tsl.timecreated AS timeawarded, ctx.id AS contextid, tsl.points AS coursepoint
                FROM {context} ctx
                INNER JOIN {tool_skills_courses} tsc ON ctx.instanceid = tsc.courseid
                INNER JOIN {tool_skills_awardlogs} tsl ON tsl.method = 'course' AND tsc.id = tsl.methodid
                INNER JOIN {tool_skills_userpoints} up ON up.skill = tsc.skill
                INNER JOIN {tool_skills} ts ON ts.id = up.skill
                WHERE ctx.id {$contextsql} AND up.userid = :userid
                ORDER BY ts.id ASC";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $contextlist->get_user()->id,
        ];
        $userpoints = $DB->get_records_sql($sql, $params + $contextparams);

        self::export_user_points(
            get_string('privacy:awardlogs', 'tool_skills'),
            array_filter(
                $userpoints,
                function(stdClass $point) use ($contextlist): bool {
                    return $point->userid == $contextlist->get_user()->id;
                }
            ),
            $user
        );
    }

    /**
     * Helper function to export user points.
     *
     * The array of "user points" is actually the result returned by the SQL in export_user_data.
     * It is more of a list of user points earned for skills. Which is why it needs to be grouped by context id.
     *
     * @param string $path The path in the export (relative to the current context).
     * @param array $userpoints Array of user points to export the logs for.
     * @param stdclass $user User record object.
     */
    private static function export_user_points(string $path, array $userpoints, $user) {

        $userpointsbycontextid = self::group_by_property($userpoints, 'contextid');
        foreach ($userpointsbycontextid as $contextid => $points) {
            $context = \context::instance_by_id($contextid);
            $skillsbyid = self::group_by_property($points, 'skill');
            foreach ($skillsbyid as $skillid => $skills) {

                $skilldata = (object) array_map(function($skill) use ($user) {
                    if ($user->id == $skill->userid) {
                        $skillobj = \tool_skills\skills::get($skill->skill);
                        return [
                            'username' => fullname(\core_user::get_user($skill->userid)),
                            'skill' => $skillobj->get_name(),
                            'points' => $skill->coursepoint,
                            'timeawarded' => $skill->timeawarded ? transform::datetime($skill->timeawarded) : '-',
                        ];
                    }
                }, $skills);

                if (!empty($skilldata)) {
                    $context = \context::instance_by_id($contextid);
                    // Fetch the generic context data.
                    // Export data.
                    writer::with_context($context)->export_data(
                        [$path],
                        $skilldata
                    );
                }
            };
        }
    }

    /**
     * Helper function to group an array of stdClasses by a common property.
     *
     * @param array $classes An array of classes to group.
     * @param string $property A common property to group the classes by.
     * @return array list of element seperated by given property.
     */
    private static function group_by_property(array $classes, string $property): array {
        return array_reduce(
            $classes,
            function (array $classes, stdClass $class) use ($property): array {
                $classes[$class->{$property}][] = $class;
                return $classes;
            },
            []
        );
    }

}
