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
 * Tool Skills - Manage course skills list.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require config.
require(__DIR__.'/../../../../config.php');

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');

// Get parameters.
$skillid = required_param('id', PARAM_INT);
// Get the skill.
$skill = $DB->get_record('tool_skills', ['id' => $skillid], '*', MUST_EXIST);

// Get system context.
$context = \context_system::instance();

// Login check required.
require_login();
// Access checks.
require_capability('tool/skills:viewotherspoints', $context);

// Prepare the page (to make sure that all necessary information is already set even if we just handle the actions as a start).
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/skills/manage/usersreport.php', ['id' => $skillid]));
$PAGE->set_cacheable(false);

// Skills points string.
$pointstr = get_string('skillpoints', 'tool_skills', format_string($skill->name));
$PAGE->set_heading($pointstr);

// Further prepare the page.
$PAGE->set_title($pointstr);

$PAGE->navbar->add(get_string('profile', 'core'), new moodle_url('/user/profile.php'));
$PAGE->navbar->add(get_string('skills', 'tool_skills'),
    new moodle_url('/admin/tool/skills/manage/usersreport.php', ['id' => $skillid]));

// Build skills table.
$filterset = new tool_skills\table\users_skills_filterset;
// Users skills list of table.
$table = new \tool_skills\table\users_skills($skillid);
$table->define_baseurl($PAGE->url);
$table->set_filterset($filterset);

// Header.
echo $OUTPUT->header();

// Skills description.
echo get_string('skillsotherspoint_desc', 'tool_skills');

$table->out(50, true);

// Footer.
echo $OUTPUT->footer();
