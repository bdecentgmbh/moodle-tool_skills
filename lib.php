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
 * Tool Skills - Common library functions.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use core_user\output\myprofile\tree;

/**
 * Add the link in course secondary navigation menu to open the skills list page.
 *
 * @param  navigation_node $navigation
 * @param  stdClass $course
 * @param  context_course $context
 * @return void
 */
function tool_skills_extend_navigation_course(navigation_node $navigation, stdClass $course, $context) {
    global $PAGE;

    $addnode = $context->contextlevel === CONTEXT_COURSE;
    $addnode = $addnode && has_capability('tool/skills:managecourseskillslist', $context);
    if ($addnode) {
        $id = $context->instanceid;
        $url = new moodle_url('/admin/tool/skills/manage/courselist.php', [
            'courseid' => $id,
        ]);
        $node = $navigation->create(get_string('manageskills', 'tool_skills'), $url, navigation_node::TYPE_SETTING, null, null);
        $node->add_class('manage-tool-skills');
        $node->set_force_into_more_menu(false);
        $node->set_show_in_secondary_navigation(true);
        $node->key = 'manage-tool-skills';

        if (empty($navigation->get_children_key_list())) {
            $navigation->add_node($node, null);
        } else {
            $navigation->add_node($node, 'coursereports');
        }
    }
}

/**
 * Defines learningtools nodes for my profile navigation tree.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser is the user viewing profile, current user ?
 * @param stdClass $course course object
 *
 * @return bool
 */
function tool_skills_myprofile_navigation(tree $tree, $user, $iscurrentuser, $course) {
    global $USER, $OUTPUT;

    // Get the learningtools category.
    if (!array_key_exists('toolskills', $tree->__get('categories'))) {
        // Create the category.
        $categoryname = get_string('skillprofilecategory', 'tool_skills');
        $category = new core_user\output\myprofile\category('toolskills', $categoryname, 'privacyandpolicies');
        $tree->add_category($category);
    } else {
        // Get the existing category.
        $category = $tree->__get('categories')['toolskills'];
    }

    if ($iscurrentuser) {
        $systemcontext = \context_system::instance();

        // Render the "Skills earned" accordion of the user's skills.
        $skillscontext = \tool_skills\helper::get_user_profile_skills_context($USER->id);
        if (!empty($skillscontext['hasskills'])) {
            $content = $OUTPUT->render_from_template('tool_skills/profile_skills', $skillscontext);
            $skillsnode = new core_user\output\myprofile\node(
                'toolskills',
                'toolskills_earned',
                '',
                null,
                null,
                $content,
                null,
                'toolskills-profile-skills-node'
            );
            $tree->add_node($skillsnode);
        }

        // Manage skills link for users who can manage them.
        if (has_capability('tool/skills:manage', $systemcontext)) {
            $link = new moodle_url('/admin/tool/skills/manage/list.php');
            $skillstr = html_writer::link($link, get_string('skills:manage', 'tool_skills'));
            $managenode = new core_user\output\myprofile\node('toolskills', 'manageskills', $skillstr, null, null);
            $tree->add_node($managenode);
        }
    }
    return true;
}


/**
 * Get icon mapping for font-awesome.
 * @return string[]
 */
function tool_skills_get_fontawesome_icon_map() {
    return [
        'tool_skills:f/archive' => 'fa-archive',
        'tool_skills:f/active' => 'fa-undo',
    ];
}


/**
 * File serving callback
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file was not found, just send the file otherwise and do not return anything
 */
function tool_skills_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    if ($filearea == 'levelimage') {
        $relativepath = implode('/', $args);

        $fullpath = "/$context->id/tool_skills/$filearea/$relativepath";

        $fs = get_file_storage();
        $file = $fs->get_file_by_hash(sha1($fullpath));
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, null, 0, $forcedownload, $options);
    }

    return false;
}
