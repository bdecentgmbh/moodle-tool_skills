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
 * Tool skills - Course skills helper methods.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

use single_button;

/**
 * Skills helper defined some common purpose methods to easy access.
 */
class helper {

    /**
     * Generate the button which is displayed on top of the templates table. Helps to create templates.
     *
     * @param string $tab Currently selected tab of the skills list. (active or archive)
     * @param bool $filtered Is the table result is filtered.
     * @return string The HTML contents to display the create templates button.
     */
    public static function skills_buttons($tab, $filtered=false) {
        global $OUTPUT, $PAGE, $CFG;

        require_once($CFG->dirroot. '/admin/tool/skills/locallib.php');

        $button = '';

        // Users with manageskills capability to create a new skill.
        if (has_capability('tool/skills:manage', $PAGE->context)) {
            // Setup create template button on page.
            $caption = get_string('createskill', 'tool_skills');
            $editurl = new \moodle_url('/admin/tool/skills/manage/edit.php', ['sesskey' => sesskey()]);

            // IN Moodle 4.2, primary button param depreceted.
            $primary = defined('single_button::BUTTON_PRIMARY') ? single_button::BUTTON_PRIMARY : true;
            $singlebutton = new single_button($editurl, $caption, 'get', $primary);
            $button .= $OUTPUT->render($singlebutton);
        }

        // Filter form.
        $button .= \html_writer::start_div('filter-form-container');
        $button .= \html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('i/filter', 'Filter'), [
            'id' => 'tool-skills-filter',
            'class' => 'sort-toolskills btn btn-primary ml-2 ' . ($filtered ? 'filtered' : ''),
        ]);
        $filter = new \tool_skills_table_filter(null, ['t' => $tab]);
        $button .= \html_writer::tag('div', $filter->render(), ['id' => 'tool-skills-filterform', 'class' => 'hide']);
        $button .= \html_writer::end_div();

        return $button;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function get_skills_list() {
        global $DB;
        // List of skills available.
        $skills = $DB->get_records('tool_skills', []);
        array_walk($skills, function(&$skill) {
            $skill = \tool_skills\skills::get($skill->id);
        });

        return $skills;
    }

    /**
     * Get the list of completed skills of the user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_user_completedskills(int $userid) {
        global $DB;
        // List of skills available.
        $skills = \tool_skills\user::get($userid)->get_user_skills();

        $completed = [];
        foreach ($skills as $skill) {
            $skillpoint = $skill->skillobj->get_points_to_earnskill();
            if ($skillpoint <= 0) {
                continue;
            }

            $points = $skill->userpoints->points ?? 0;
            $percentage = ($points / $skillpoint) * 100;

            if ($percentage >= 100) {
                $completed[] = $skill->skill;
            }
        }

        return !empty($completed) ? array_unique($completed) : [];
    }

    /**
     * Calculate the skills total points assigned for the given courses.
     *
     * @param array $courseids
     * @return int
     */
    public static function get_courses_skill_points(array $courseids) {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'skp');

        $sql = "SELECT tsl.skill, MAX(tsl.points) AS skillpoints
        FROM {tool_skills_levels} tsl
        JOIN {tool_skills_courses} tsc ON tsc.skill = tsl.skill
        WHERE tsc.status = 1 AND tsc.courseid $insql
        GROUP BY tsl.skill";

        $skills = $DB->get_records_sql($sql, $inparams);

        $skillpoints = array_sum(array_column($skills, 'skillpoints'));

        return $skillpoints;
    }

    /**
     * Get addon extend method.
     *
     * @param string $method
     * @return array
     */
    public static function get_addon_extend_method($method) {
        $addon = new \tool_skills\plugininfo\skilladdon();
        $methods = $addon->get_plugins_base($method);
        return $methods;
    }

    /**
     * Extend the remove skills addon.
     *
     * @param int $skillid Id of the skill.
     * @return void
     */
    public static function extend_addons_remove_skills(int $skillid) {
        // Extend the method from sub plugins.
        $methods = self::get_addon_extend_method('remove_skills');
        foreach ($methods as $method) {
            // Trigger the skill id.
            $method->remove_skills($skillid);
        }

    }


    /**
     * Remove course instance.
     *
     * @param int $courseid Course ID.
     * @return void
     */
    public static function extend_addons_remove_course_instance(int $courseid) {
        // Extend the method from sub plugins.
        $methods = self::get_addon_extend_method('remove_course_instance');
        foreach ($methods as $method) {
            // Trigger the skill id.
            $method->remove_course_instance($courseid);
        }
    }

    /**
     * Add the activity method user skills data .
     *
     * @param int $point
     * @return void
     */
    public static function extend_addons_add_userskills_data(&$point) {
        // Extend the method from sub plugins.
        $methods = self::get_addon_extend_method('add_userskills_data');
        foreach ($methods as $method) {
            // Trigger the skill id.
            $method->add_userskills_data($point);
        }
    }

    /**
     * Add to the user points content in profile page.
     *
     * @param int $skillstr Course ID.
     * @param stdclass $data Data.
     * @return void
     */
    public static function extend_addons_add_user_points_content(&$skillstr, $data) {
        // Extend the method from sub plugins.
        $methods = self::get_addon_extend_method('add_user_points_content');
        foreach ($methods as $method) {
            // Trigger the skill id.
            $method->add_user_points_content($skillstr, $data);
        }
    }

    /**
     * Add the activity method user skills data .
     *
     * @param \tool_skills\allocation_method $skillobj
     * @return string
     */
    public static function extend_addons_get_allocation_method($skillobj) {
        // Extend the method from sub plugins.
        $methods = self::get_addon_extend_method('get_allocation_method');
        foreach ($methods as $method) {
            // Trigger the skill id.
            $result = $method->get_allocation_method($skillobj);

            // Find the allocation method, break the check.
            if ($result) {
                break;
            }
        }
        return $result ?? '';
    }

}
