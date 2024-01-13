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
require_once($CFG->libdir.'/tablelib.php');

// Get parameters.
$courseid = required_param('courseid', PARAM_INT);
$skillid = optional_param('skill', null, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

if ($skillid && $courseid) {
    $skillcourse = $DB->get_record('tool_skills_courses', ['skill' => $skillid, 'courseid' => $courseid]);
}

// Optional params.
$action = optional_param('action', null, PARAM_ALPHAEXT);

// Get system context.
$context = \context_course::instance($courseid);

// Login check required.
require_login();
// Access checks.
require_capability('tool/skills:managecourseskills', $context);

// Prepare the page (to make sure that all necessary information is already set even if we just handle the actions as a start).
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/skills/manage/courselist.php', ['courseid' => $courseid]));
$PAGE->set_cacheable(false);
$PAGE->set_course($course);
$PAGE->set_heading(format_string($course->fullname));

// Process actions.
if ($action !== null && confirm_sesskey()) {
    // Every action is based on a skill, thus the skill ID param has to exist.
    $skillid = required_param('skill', PARAM_INT);

    // Start the query transaction snapshots.
    $transaction = $DB->start_delegated_transaction();

    // Perform the requested action.
    switch ($action) {

        case "disable":
            // Disable the skill.
            \tool_skills\form\course_form::update_status($skillid, $courseid, false);
            break;
        case "enable":
            // Enable the skill.
            \tool_skills\form\course_form::update_status($skillid, $courseid, true);
            break;
    }

    // Allow to update the changes to database.
    $transaction->allow_commit();

    // Redirect to the same page.
    redirect($PAGE->url);
}

// Further prepare the page.
$PAGE->set_title(get_string('courseskills', 'tool_skills'));
$PAGE->navbar->add(get_string('mycourses', 'core'), new moodle_url('/course/index.php'));
$PAGE->navbar->add(format_string($course->shortname), new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('skills', 'tool_skills'),
    new moodle_url('/admin/tool/skills/manage/courselist.php', ['courseid' => $courseid]));

// Build skills table.
$table = new \tool_skills\table\course_skills_table($courseid);
$table->define_baseurl($PAGE->url);

// Header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('assignskills', 'tool_skills'));

// Skills description.
echo get_string('assignskills_desc', 'tool_skills');

// Create skills button to create new skill.
$createbutton = '';
if (has_capability('tool/skills:manage', \context_system::instance())) {
    $createbutton .= $OUTPUT->box_start();
    $createbutton .= $OUTPUT->single_button(
            new \moodle_url('/admin/tool/skills/manage/edit.php', ['sesskey' => sesskey()]),
            get_string('createskill', 'tool_skills'), 'get');
    $createbutton .= $OUTPUT->box_end();
}

$countmenus = $DB->count_records('tool_skills');
if ($countmenus < 1) {

    $table->out(0, true);

    echo $createbutton;

} else {

    echo $createbutton;

    $table->out(50, true);

    $PAGE->requires->js_amd_inline('require(["jquery"], function($) {

        // Make the status toggle check and uncheck on click on status update toggle.
        var form = document.querySelectorAll(".toolskills-status-switch");
        form.forEach((switche) => {
            switche.addEventListener("click", function(e) {
                var form = e.currentTarget.querySelector("input[type=checkbox]");
                form.click();
            });
        });

    })');

    $PAGE->requires->js_call_amd('tool_skills/skills', 'init', ['courseid' => $courseid]);
}

// Footer.
echo $OUTPUT->footer();
