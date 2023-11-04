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
    $addnode = $addnode && has_capability('tool/skills:managecourseskills', $context); // TODO: Custom capability.
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
        $navigation->add_node($node, 'gradebooksetup');

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
    global $USER;

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
        $skills = \tool_skills\user::get($USER->id)->get_user_skills();

        $newskills = [];
        foreach ($skills as $id => $data) {
            $skillid = $data->skill; // Skill id.
            $newskills[$skillid][$id] = $data;
            $skillslist[$skillid] = $data->skillobj; // Skill instance.
        }

        foreach ($newskills as $skillid => $skills) {

            $skill = $skillslist[$skillid];
            $skillpoints = $skill->get_points_to_earnskill();

            $userskillpoint = $skill->get_user_skill($USER->id, false);
            $earnedstring = html_writer::tag('b',
                " (" . get_string('earned', 'tool_skills') . ": " . ($userskillpoint->points ?? 0) . ")");
            // Skill name.
            $skillstr = html_writer::tag('h5', $skillslist[$skillid]->get_name());
            // Point to completion this skill.
            $skillstr .= html_writer::tag('p', 'Points to complete this skill: ' . $skillpoints . $earnedstring,
                ['class' => 'skill-'.$skill->get_data()->identitykey]);

            $skillstr .= html_writer::start_tag('ul'); // Start the list of skills courses.

            foreach ($skills as $id => $data) {
                // Course skill object.
                $skillcourse = $data->skillcourse;
                $pointstoearn = $skillcourse->get_points_earned_fromcourse();
                $courseurl = new moodle_url('/course/view.php', ['id' => $data->courseid]);

                // Points earned from this course.
                $pointsfromcourse = $skillcourse->get_user_earned_points($USER->id);

                $course = $data->skillcourse->get_course();
                $li = html_writer::link($courseurl, format_string($course->fullname));

                $coursepointstr = "Points for completion" . " : " . $pointstoearn;
                $coursepointstr .= html_writer::tag('b',
                    " (".get_string('earned', 'tool_skills') . ": " .( $pointsfromcourse ?? 0) . ")" );

                $li .= html_writer::tag('p', $coursepointstr, ['class' => 'skills-points-'.$course->shortname]);

                $skillstr .= html_writer::tag('li', $li);
            }

            $skillstr .= html_writer::end_tag('ul'); // End the skill list.

            $coursenode = new core_user\output\myprofile\node('toolskills', "skill_".$skill->get_data()->id,
                $skillstr, null, null);

            $tree->add_node($coursenode);
        }

    }
    return true;
}