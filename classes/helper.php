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
     * Skills section on the user profile: not shown.
     * @var int
     */
    public const PROFILEDISPLAY_DISABLED = 0;

    /**
     * Skills section on the user profile: each skill with its courses and activities.
     * @var int
     */
    public const PROFILEDISPLAY_DETAILED = 1;

    /**
     * Skills section on the user profile: the total of each skill only, without course and activity rows.
     * @var int
     */
    public const PROFILEDISPLAY_TOTALS = 2;

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
     * What the Skills section of the user profile shows, from the tool_skills/profiledisplay setting.
     *
     * @return int One of the PROFILEDISPLAY_* constants; PROFILEDISPLAY_DETAILED until the setting is saved.
     */
    public static function get_profile_display(): int {
        $display = get_config('tool_skills', 'profiledisplay');
        return $display === false ? self::PROFILEDISPLAY_DETAILED : (int) $display;
    }

    /**
     * Whether the current user can open the Skills page of a course, which also shows its Skills tab.
     *
     * Users who manage the course's skills always can, so they can enable skills. Others need the
     * capability, a course that awards a skill, and the Skills section of the profile not to be disabled.
     *
     * @param \context_course $context
     * @return bool
     */
    public static function can_view_course_page(\context_course $context): bool {
        if (has_capability('tool/skills:managecourseskillslist', $context)) {
            return true;
        }
        return has_capability('tool/skills:viewcourseskills', $context)
            && self::get_profile_display() !== self::PROFILEDISPLAY_DISABLED
            && self::get_course_skills($context->instanceid);
    }

    /**
     * The skills a course awards: enabled in the course, and enabled and not archived site-wide.
     *
     * @param int $courseid
     * @return string[] Skill names, keyed by skill id, sorted by name.
     */
    public static function get_course_skills(int $courseid): array {
        global $DB;

        $names = $DB->get_records_sql_menu(
            'SELECT s.id, s.name
               FROM {tool_skills_courses} sc
               JOIN {tool_skills} s ON s.id = sc.skill
              WHERE sc.courseid = :courseid AND sc.status = 1 AND s.status = 1 AND s.archived = 0
           ORDER BY s.name',
            ['courseid' => $courseid]
        );
        return array_map('format_string', $names);
    }

    /**
     * Whether the current user can open the Participants' progress page of a course.
     *
     * Everyone who manages the course's skills can, with the points from the course only; with
     * tool/skills:viewcourseparticipants also the participants' levels and total points.
     *
     * @param \context_course $context
     * @return bool
     */
    public static function can_view_course_participants(\context_course $context): bool {
        return has_any_capability(['tool/skills:viewcourseparticipants', 'tool/skills:managecourseskillslist'], $context);
    }

    /**
     * The tertiary navigation of a course's Skills pages, as on the Participants page: a menu of the pages
     * the user can open, and outside the Manage page its button.
     *
     * @param \context_course $context
     * @param string $current The current page: overview, progress or manage.
     * @return string HTML, empty when the user can open the overview only.
     */
    public static function get_course_navigation(\context_course $context, string $current): string {
        global $PAGE;

        $params = ['courseid' => $context->instanceid];
        $pages = ['overview' => [new \moodle_url('/admin/tool/skills/course.php', $params), 'overview']];
        if (self::can_view_course_participants($context)) {
            $pages['progress'] = [new \moodle_url('/admin/tool/skills/progress.php', $params), 'participantsprogress'];
        }
        $canmanage = has_capability('tool/skills:managecourseskillslist', $context);
        if ($canmanage) {
            $pages['manage'] = [new \moodle_url('/admin/tool/skills/manage/courselist.php', $params), 'managecourseskills'];
        }
        if (count($pages) < 2) {
            return '';
        }

        $options = [];
        foreach ($pages as [$url, $label]) {
            $options[$url->out(false)] = get_string($label, 'tool_skills');
        }
        $menu = new \core\output\select_menu('toolskillsnavigation', $options, $pages[$current][0]->out(false), true);
        $menu->set_label(get_string('coursenavigation', 'tool_skills'), ['class' => 'visually-hidden']);
        $output = $PAGE->get_renderer('core');
        $html = \html_writer::div(
            $output->render_from_template('core/tertiary_navigation_selector', $menu->export_for_template($output)),
            'navitem'
        );
        if ($canmanage && $current !== 'manage') {
            $button = $output->single_button($pages['manage'][0], get_string('managecourseskills', 'tool_skills'), 'get');
            $html .= \html_writer::div($button, 'navitem ms-auto');
        }
        return \html_writer::div($html, 'tertiary-navigation d-flex flex-wrap align-items-center gap-2 mb-3');
    }

    /**
     * What an award gives, in the words of the course and activity skill lists, for example "Points - 40".
     *
     * @param int $type One of the skills::COMPLETION* constants.
     * @param int|null $points Points of a points award.
     * @param int|null $levelid Level of a level award.
     * @return string Empty for skills::COMPLETIONNOTHING.
     */
    public static function get_award_label(int $type, ?int $points, ?int $levelid): string {
        switch ($type) {
            case skills::COMPLETIONPOINTS:
                return get_string('completionpoints', 'tool_skills') . ' - ' . $points;
            case skills::COMPLETIONPOINTSGRADE:
                return get_string('completionpointsgrade', 'tool_skills');
            case skills::COMPLETIONSETLEVEL:
                return get_string('completionsetlevel', 'tool_skills') . ' - ' . level::get($levelid)->get_name();
            case skills::COMPLETIONFORCELEVEL:
                return get_string('completionforcelevel', 'tool_skills') . ' - ' . level::get($levelid)->get_name();
            default:
                return '';
        }
    }

    /**
     * Build the Skills profile template context for a user.
     *
     * Produces the data consumed by the tool_skills/profile_skills Mustache template: one entry per
     * skill the user is working towards, each with its earned/total points, current level (name,
     * colour, image), the pills added by plugins through the extend_profile_skill hook and, unless only
     * the totals are shown, the per-course points breakdown.
     *
     * With a course, it is the context of the course's Skills page (tool_skills/course_skills): the
     * course's skills, each with its course row only, what the course awards (award) and the points from
     * the course. With user id 0 as well, the skills carry no personal data (hidepoints, no pills).
     *
     * @param int $userid The user to build the skills summary for, or 0 with a course.
     * @param bool $detailed Include the per-course (and addon per-activity) breakdown.
     * @param int|null $courseid The course of a course's Skills page.
     * @return array Template context: ['hasskills' => bool, 'detailed' => bool, 'skills' => array, 'uniqid' => string].
     */
    public static function get_user_profile_skills_context(int $userid, bool $detailed = true, ?int $courseid = null): array {

        $records = \tool_skills\user::get($userid)->get_user_skills($courseid);

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

            // Let plugins add pills next to the points, e.g. points removed by decay or the user's target.
            $pills = [];
            if ($userid) {
                $hook = new \tool_skills\hook\extend_profile_skill($userid, $skill, $earned);
                \core\di::get(\core\hook\manager::class)->dispatch($hook);
                $pills = $hook->get_pills();
            }

            // Per-course points breakdown, left out when only the totals are shown.
            $courses = [];
            if (!$detailed) {
                $courserecords = [];
            }
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
                // Let addons contribute their own per-course content (e.g. activity breakdown), on the
                // course's Skills page with what each item awards.
                $record->showaward = $courseid !== null;
                $addon = '';
                self::extend_addons_add_user_points_content($addon, $record);
                $award = $record->showaward
                    ? self::get_award_label((int) $record->uponcompletion, (int) $record->points, (int) $record->level)
                    : '';
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
                    'award' => $award,
                    'hasawards' => $award !== '' || $addon !== '',
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
                'haspills' => !empty($pills),
                'pills' => $pills,
                'haslearningtime' => !empty($data->learningtime),
                'learningtime' => !empty($data->learningtime) ? format_time($data->learningtime) : '',
                'hascourses' => !empty($courses),
                'courses' => $courses,
                'hasreport' => $canreport,
                'reporturl' => (new \moodle_url('/admin/tool/skills/manage/usersreport.php', ['id' => $skillid]))->out(false),
                // The totals-only cards show a body only for the learning time or the report link.
                'hasbody' => !empty($data->learningtime) || $canreport,
                // Expand the first skill by default so the panel is not empty on open.
                'expanded' => $index === 0,
                // The course's Skills page: without a user no points; with one, the points from the course.
                'hidepoints' => !$userid,
                'hascoursepoints' => $courseid && $userid,
                'coursepoints' => $courseid && $userid ? points_history::get_course_points($userid, $skillid, $courseid) : 0,
            ];
            $index++;
        }

        return [
            'hasskills' => !empty($skills),
            'detailed' => $detailed,
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
