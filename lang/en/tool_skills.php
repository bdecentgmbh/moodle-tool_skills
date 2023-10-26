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
 * Tool Skills - Language strings.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access');

$string['pluginname'] = 'Skill award';
$string['skills'] = 'Skills';
// ...Skills capabilities.
$strings['skills:manage'] = 'Manage skills';
$strings['skills:managecourseskills'] = 'Manage course skills';
// ...error strings.
$string['error:skillsnotfound'] = 'Skill record not found for the given id';
$string['error:identityexists'] = 'Given skill identity is exists, Please use unique value';
$string['error:numeric'] = 'Value should be in numbers';
// ...Form field strings.
$string['key'] = 'Key';
$string['status'] = 'Status';
$string['status_help'] = 'Enable/Disable the skill';
$string['disabled'] = 'Disabled';
$string['enabled'] = 'Enabled';
$string['availableincoursecategories'] = 'Available in course categories';
$string['availableincoursecategories_help'] = '';
$string['skilltitle'] = 'Skill name';
$string['skilltitle_help'] = 'Name of the skill';
$string['identitykey'] = 'Key';
$string['identitykey_help'] = 'Key to identity the skill, this should be unique value';
$string['learningtime'] = 'Learning time';
$string['learningtime_help'] = 'Time to spend in the course to complete this skill';
$string['skillcolor'] = 'Skill color';
$string['skillcolor_help'] = 'Color of the skill';
// ...List page strings.
$string['skillslist'] = 'List of skills';
$string['skillslisthead'] = 'Manage skills';
$string['skillslist_desc'] = 'Create a new skill and edit exsiting skills';
$string['createskill'] = 'Create skill';
$string['create'] = 'Create';
$string['editskill'] = 'Edit skill';
$string['edit'] = 'Edit';
// ...Delete message.
$string['skillsdeleted'] = 'Skills deleted';
$string['deleteskill'] = 'Are you sure! do you want to delete this skill and its levels';
$string['skillsnothingtodisplay'] = 'Skills are not created yet or not in use, Create a new skill using the below create button';
// ...Levels form fields string.
$string['skillslevels'] = 'Levels';
$string['levelscount'] = 'Number of levels';
$string['updatelevelscount'] = 'Update levels count';
// ... Levels properties.
$string['levelsname'] = 'Level #{$a} name';
$string['levelspoint'] = 'Level #{$a} point';
$string['levelscolor'] = 'Level #{$a} color';
$string['levelsimage'] = 'Level #{$a} image';
$string['levelsnohead'] = 'Level #{$a} info';
// ...course menu strings.
$string['courseskills'] = 'Set course skills';
$string['manageskills'] = 'Manage skills';
$string['uponcompletion'] = 'Upon course completion';
$string['completionnothing'] = 'Nothing';
$string['completionpoints'] = 'Points';
$string['completionsetlevel'] = 'Set level';
$string['completionforcelevel'] = 'Force level';
$string['completionlevel'] = 'Level';
// ...Course skill table strings.
$string['assignskills'] = 'Assign skills';
$string['assignskills_desc'] = 'Customize the skills associated with this course. Activate or deactivate specific skills to align with your teaching objectives. By default, all skills are disabled. Simply enable the ones that fit your course content and goals. ';
// ...Profile page skills result category.
$string['skillprofilecategory'] = 'Skills earned';
$string['earned'] = 'Earned';
