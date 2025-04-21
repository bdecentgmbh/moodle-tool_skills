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

$string['active'] = 'Active';
$string['activeskills'] = 'Active skills';
$string['activeskillwarning'] = 'Are you sure! do you want to activate this skill and its levels';
$string['activitiesentity'] = 'Activities';
$string['activitiestatsentity'] = 'Activity completion';
$string['activityname'] = 'Activity name';
$string['archive'] = 'Archive';
$string['archiveskill'] = 'Are you sure! do you want to archive this skill and its levels';
$string['archiveskills'] = 'Archived skills';
$string['assignskills'] = 'Assign skills';
$string['assignskills_desc'] = 'Customize the skills associated with this course. Activate or deactivate specific skills to align with your teaching objectives. By default, all skills are disabled. Simply enable the ones that fit your course content and goals. ';
$string['availableincoursecategories'] = 'Available in course categories';
$string['availableincoursecategories_help'] = 'Select the categories to make this skill available to the courses in that category only. If no category is selected, the course will be available globally across all categories.';
$string['baselevelheading'] = 'Base level info';
$string['baselevelname'] = 'Base level name';
$string['baselevelname_help'] = 'This is the name assigned to the base level of the skill. The base level represents the starting point for skill progression.';
$string['baselevelpoint'] = 'Base level point';
$string['baselevelpoint_help'] = 'The number of points required to achieve the base level of the skill. This indicates the minimum proficiency level for the skill.';
$string['categoryidnumber'] = 'Category idnumber';
$string['categoryname'] = 'Category name';
$string['categoryvisiblity'] = 'Category visibility';
$string['complete'] = 'Complete';
$string['complete_fail'] = 'Failed';
$string['complete_pass'] = 'Passed';
$string['completionforcelevel'] = 'Force level';
$string['completionforcelevelresult'] = 'Force to ';
$string['completionlevel'] = 'Level';
$string['completionlevel_help'] = 'Choose the desired skill level for this course. Upon completion, the student will receive the corresponding number of points required to achieve the selected level.';
$string['completionnothing'] = 'Nothing';
$string['completionnothingresult'] = 'Nothing';
$string['completionpoints'] = 'Points';
$string['completionpoints_help'] = 'Enter the number of skill points to be awarded or deducted. Use a positive number to add points and a negative number to deduct points.
<br>Example:
<li>Entering "50" will add 50 points.</li>
<li>Entering "-20" will deduct 20 points.</li>';
$string['completionpointsgrade'] = 'Points by grade';
$string['completionpointsresult'] = 'Points upto ';
$string['completionsetlevel'] = 'Set level';
$string['completionsetlevelresult'] = 'Set to ';
$string['conditionassignedusers'] = 'Relative role users';
$string['conditionusercohort'] = 'Users in same cohort';
$string['coursecount'] = 'Number of courses';
$string['courseskills'] = 'Set course skills';
$string['coursestatus'] = 'Assign skill to course';
$string['coursestatus_help'] = 'Select enable to assign the skill to course, then user will receive the points/level for course completion';
$string['coursesused'] = 'Courses using the skill';
$string['create'] = 'Create';
$string['createskill'] = 'Create skill';
$string['deleteskill'] = 'Are you sure! do you want to delete this skill and its levels';
$string['depth'] = 'Depth';
$string['description'] = 'Description';
$string['description_help'] = 'Enter the description for admin purpose';
$string['disabled'] = 'Disabled';
$string['earned'] = 'Earned';
$string['edit'] = 'Edit';
$string['editskill'] = 'Edit skill';
$string['enabled'] = 'Enabled';
$string['entitycategory'] = 'Category';
$string['error:identityexists'] = 'Given skill identity is exists, Please use unique value';
$string['error:numeric'] = 'Value should be in numbers';
$string['error:skillsnotfound'] = 'Skill record not found for the given id';
$string['formtab'] = 'Skills';
$string['grade'] = 'Grade';
$string['identitykey'] = 'Key';
$string['identitykey_help'] = 'Key to identity the skill, this should be unique value';
$string['incomplete'] = 'In complete';
$string['key'] = 'Key';
$string['learningtime'] = 'Learning time';
$string['learningtime_help'] = 'Time to spend in the course to complete this skill';
$string['levelscount'] = 'Number of levels';
$string['levelscount_help'] = 'Choose the number of levels that exist for this skill. Each level may have a specific number of points required for achievement.';
$string['levelsname'] = 'Level #{$a} name';
$string['levelsname_help'] = 'Enter the name for level';
$string['levelsnohead'] = 'Level #{$a} info';
$string['levelspoint'] = 'Level #{$a} point';
$string['levelspoint_help'] = 'Enter the number of points required to achieve Level. This field is required.';
$string['manageskills'] = 'Manage skills';
$string['maximum'] = 'Maximum';
$string['modcompletionstatus'] = 'Completion status';
$string['modname'] = 'Mod name';
$string['path'] = 'Category path';
$string['pluginname'] = 'Skills';
$string['pointscomplete'] = 'Points to complete this skill: {$a} ';
$string['pointsearned'] = 'Points earned';
$string['pointsforcompletion'] = 'Max points';
$string['privacy:awardlogs'] = 'Points awarded';
$string['privacy:metadata:awardlogs'] = 'Metadata for logs recording user points awarded for each method';
$string['privacy:metadata:awardlogs:method'] = 'Method by which the user earned points (course or activity)';
$string['privacy:metadata:awardlogs:methodid'] = 'ID of the method record detailing how points were earned';
$string['privacy:metadata:awardlogs:points'] = 'Points awarded for the skill to the user';
$string['privacy:metadata:awardlogs:timecreated'] = 'Time when awards were logged';
$string['privacy:metadata:awardlogs:userid'] = 'User ID for points awarded';
$string['privacy:metadata:userpoints'] = 'Metadata for points earned by the user for skills';
$string['privacy:metadata:userpoints:points'] = 'Points earned by the user for the skill';
$string['privacy:metadata:userpoints:skill'] = 'ID of the skill associated with points';
$string['privacy:metadata:userpoints:timecreated'] = 'Time when points were initially earned';
$string['privacy:metadata:userpoints:timemodified'] = 'Time when points were modified';
$string['privacy:metadata:userpoints:userid'] = 'User ID associated with earned points';
$string['privacy:metadata:userpointsexplanation'] = 'The skills store the points users earned for the skill and log the method by which they earned it, either through course completion or activity completion.';
$string['privacy:userpoint'] = 'User point';
$string['skillpoints'] = '{$a} - users points';
$string['skillproficients'] = 'Users that are proficient in this skill';
$string['skillprofilecategory'] = 'Skills earned';
$string['skills'] = 'Skills';
$string['skills:manage'] = 'Manage skills';
$string['skills:managecourseskillslist'] = 'Manage course skills';
$string['skills:viewotherspoints'] = 'View others points';
$string['skillsdatasource'] = 'Skills';
$string['skillsdeleted'] = 'Skills deleted';
$string['skillslevel'] = 'Level';
$string['skillslevels'] = 'Levels';
$string['skillslist'] = 'List of skills';
$string['skillslist_desc'] = 'Create a new skill and edit exsiting skills';
$string['skillslisthead'] = 'Manage skills';
$string['skillsnothingtodisplay'] = 'Skills are not created yet or not in use, Create a new skill using the create button';
$string['skillsotherspoint_desc'] = 'This table displays the points earned by other users in this skill. It provides an overview of the achievements and progress of peers within the same skill category';
$string['skillsrpeort'] = 'Skills';
$string['skillstats'] = 'Skill statistics';
$string['skilltitle'] = 'Skill name';
$string['skilltitle_help'] = 'Name of the skill';
$string['skillusers'] = 'Users that have any points for skill';
$string['status'] = 'Status';
$string['status_help'] = 'Choose the status for this skill:
    <br>
    <b> Enabled: </b> The skill will be added to all courses that match the course categories setting below and can be configured by teachers. <br />
    <b> Disabled: </b> The skill will not be added to any courses and cannot be used by teachers.';
$string['subplugintype_skilladdon_plural'] = 'Skill addons';
$string['timearchived'] = 'Archived time';
$string['timemodified'] = 'Time modified';
$string['updatelevelscount'] = 'Update levels count';
$string['uponcompletion'] = 'Upon course completion';
$string['uponcompletion_help'] = '<ul><li><b>Add Points:</b> Upon course completion, award the specified number of skill points. (Note: Entering negative numbers will result in a reduction of points.)</li>
<li><b>Set Level:</b> Upon course completion, grant the points needed to reach the specified level, unless the student already has more points.</li>
<li><b>Force Level:</b> Upon course completion, adjust the points to match the amount required for the chosen level, regardless of the student\'s prior level/points. This may lead to students having fewer points than before.</li></ul>';
$string['uponcompletionresult'] = 'Upon completion';
$string['uponmodcompletion'] = 'Upon activity completion';
$string['uponmodcompletion_help'] = '<ul><li><b>Add Points:</b> Upon course module completion, award the specified number of skill points. (Note: Entering negative numbers will result in a reduction of points.)</li><li><b>Add Points By Grade:</b> Upon course module completion,  adds as many points as the grade achieved in the activity.</li>
<li><b>Set Level:</b> Upon course module completion, grant the points needed to reach the specified level, unless the student already has more points.</li>
<li><b>Force Level:</b> Upon course module completion, adjust the points to match the amount required for the chosen level, regardless of the student\'s prior level/points. This may lead to students having fewer points than before.</li></ul>';
$string['userpercentage'] = 'Percentage';
$string['userpoints'] = 'User earned points';
$string['userproficiency'] = 'Proficiency';
$string['userskillentity'] = 'User skill';
$string['usersreport'] = 'View users points for this skill';
