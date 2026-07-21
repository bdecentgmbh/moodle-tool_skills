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
    public static function skills_buttons($tab, $filtered = false) {
        global $OUTPUT, $PAGE, $CFG;

        require_once($CFG->dirroot . '/admin/tool/skills/locallib.php');

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
            'class' => 'sort-toolskills btn btn-primary' . ($filtered ? 'filtered' : ''),
        ]);
        $filter = new \tool_skills_table_filter(null, ['t' => $tab]);
        $button .= \html_writer::tag('div', $filter->render(), ['id' => 'tool-skills-filterform', 'class' => 'hide']);
        $button .= \html_writer::end_div();

        return $button;
    }

    /**
     * Get the list of all available skills.
     *
     * @return array List of skill instances.
     */
    public static function get_skills_list() {
        global $DB;
        // List of skills available.
        $skills = $DB->get_records('tool_skills', []);
        array_walk($skills, function (&$skill) {
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

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'skp');

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
     * @param string $skillstr HTML content string passed by reference.
     * @param \stdClass $data Data.
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

    /**
     * Build the "Skills earned" profile template context for a user.
     *
     * Produces the data consumed by the tool_skills/profile_skills Mustache template: one entry per
     * skill the user is working towards, each with its earned/total points, current level (name,
     * colour, image) and the per-course points breakdown.
     *
     * @param int $userid The user to build the skills summary for.
     * @return array Template context: ['hasskills' => bool, 'skills' => array, 'uniqid' => string].
     */
    public static function get_user_profile_skills_context(int $userid): array {

        $records = \tool_skills\user::get($userid)->get_user_skills();

        // Group the per-course records by their skill.
        $byskill = [];
        $skillobjs = [];
        foreach ($records as $record) {
            $byskill[$record->skill][] = $record;
            $skillobjs[$record->skill] = $record->skillobj;
        }

        $canreport = has_capability('tool/skills:viewotherspoints', \context_system::instance());

        $skills = [];
        $index = 0;
        foreach ($byskill as $skillid => $courserecords) {
            $skill = $skillobjs[$skillid];
            $data = $skill->get_data();
            $totalpoints = (int) $skill->get_points_to_earnskill();
            $userskill = $skill->get_user_skill($userid, false);
            $earned = (int) ($userskill->points ?? 0);

            // The current level is the highest level whose points threshold the user has reached.
            $currentlevel = self::get_reached_level($data->levels ?? [], $earned);
            $levelcolor = $currentlevel['color'] ?? '';
            $levelimageurl = $currentlevel ? self::get_level_image_url((int) $currentlevel['id']) : '';

            // Per-course points breakdown.
            $courses = [];
            foreach ($courserecords as $record) {
                $skillcourse = $record->skillcourse;
                $course = $skillcourse->get_course();
                $available = $skillcourse->get_points_earned_fromcourse();
                $availableint = is_numeric($available) ? (int) $available : 0;
                $courseearned = (int) ($skillcourse->get_user_earned_points($userid) ?? 0);
                // Show a points chip whenever the course awards (or deducts) points; a positive award
                // also shows the goal as "earned / available".
                $haspoints = $availableint !== 0;
                $hasgoal = $availableint > 0;
                // Let addons contribute their own per-course content (e.g. activity breakdown).
                $addon = '';
                self::extend_addons_add_user_points_content($addon, $record);
                $courses[] = [
                    'coursename' => format_string($course->fullname),
                    'courseurl' => (new \moodle_url('/course/view.php', ['id' => $record->courseid]))->out(false),
                    'courseclass' => 'toolskill-course-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $course->shortname),
                    'haspoints' => $haspoints,
                    'hasgoal' => $hasgoal,
                    'earned' => $courseearned,
                    'available' => $availableint,
                    'iscomplete' => $hasgoal && $courseearned >= $availableint,
                    'hasaddoncontent' => $addon !== '',
                    'addoncontent' => $addon,
                ];
            }

            $skills[] = [
                'skillid' => (int) $skillid,
                'identitykey' => preg_replace('/[^a-zA-Z0-9_-]/', '', $data->identitykey ?? ''),
                'name' => $skill->get_name(),
                'hascolor' => !empty($data->color),
                'color' => $data->color ?? '',
                'hascurrentlevel' => (bool) $currentlevel,
                'currentlevelname' => $currentlevel ? format_string($currentlevel['name']) : '',
                'hascurrentlevelcolor' => !empty($levelcolor),
                'currentlevelcolor' => $levelcolor,
                'currentleveltextcolor' => $levelcolor ? self::readable_text_color($levelcolor) : '',
                'haslevelimage' => $levelimageurl !== '',
                'levelimageurl' => $levelimageurl,
                'earned' => $earned,
                'hastotalpoints' => $totalpoints > 0,
                'pointstocomplete' => $totalpoints,
                'haslearningtime' => !empty($data->learningtime),
                'learningtime' => !empty($data->learningtime) ? format_time($data->learningtime) : '',
                'hascourses' => !empty($courses),
                'courses' => $courses,
                'hasreport' => $canreport,
                'reporturl' => (new \moodle_url('/admin/tool/skills/manage/usersreport.php', ['id' => $skillid]))->out(false),
                // Expand the first skill by default so the panel is not empty on open.
                'expanded' => $index === 0,
            ];
            $index++;
        }

        return [
            'hasskills' => !empty($skills),
            'skills' => $skills,
            'uniqid' => \html_writer::random_id('toolskills'),
        ];
    }

    /**
     * Return the highest level whose points threshold the given earned points reach.
     *
     * @param array $levels Levels keyed 1..N, each an array with at least 'id', 'name', 'color', 'points'.
     * @param int $earned The user's earned points for the skill.
     * @return array|null The reached level record, or null if none is reached.
     */
    protected static function get_reached_level(array $levels, int $earned): ?array {
        $levels = array_values($levels);
        usort($levels, fn($a, $b) => ((int) ($a['points'] ?? 0)) <=> ((int) ($b['points'] ?? 0)));
        $reached = null;
        foreach ($levels as $level) {
            if ($earned >= (int) ($level['points'] ?? 0)) {
                $reached = $level;
            }
        }
        return $reached;
    }

    /**
     * Build the pluginfile URL for a level's image, or an empty string when it has none.
     *
     * @param int $levelid The tool_skills_levels id.
     * @return string The image URL, or '' if no image is set.
     */
    protected static function get_level_image_url(int $levelid): string {
        $files = get_file_storage()->get_area_files(
            \context_system::instance()->id,
            'tool_skills',
            'levelimage',
            $levelid,
            'itemid, filepath, filename',
            false
        );
        if (empty($files)) {
            return '';
        }
        $file = reset($files);
        return \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);
    }

    /**
     * Pick black or white text for readable contrast against a hex background colour.
     *
     * @param string $hex A '#rgb' or '#rrggbb' colour (already validated on input).
     * @return string '#000000' or '#ffffff'.
     */
    protected static function readable_text_color(string $hex): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return '#000000';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        // Relative luminance (perceptual weights); light backgrounds get dark text.
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.6 ? '#000000' : '#ffffff';
    }
}
