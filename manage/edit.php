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
 * Tool skills - Edit skills.
 *
 * @package    tool_skills
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require config.
require(__DIR__.'/../../../../config.php');

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');

// Get parameters.
$id = optional_param('id', null, PARAM_INT);

// Get system context.
$context = context_system::instance();

// Access checks.
require_login();
require_sesskey();

require_capability('tool/skills:manage', $context);

$listurl = new moodle_url('/admin/tool/skills/manage/list.php');

// Prepare the page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/skills/manage/edit.php', array('id' => $id, 'sesskey' => sesskey())));
$PAGE->set_cacheable(false);

$PAGE->navbar->add(get_string('tools', 'admin'), new moodle_url('/admin/category.php', array('category' => 'tool')));
$PAGE->navbar->add(get_string('pluginname', 'tool_skills'), $listurl);

$PAGE->set_title(get_string('skills', 'tool_skills'));

if ($id !== null && $id > 0) {

    $PAGE->set_heading(get_string('editskill', 'tool_skills'));
    $PAGE->navbar->add(get_string('edit', 'tool_skills'));

} else {
    $PAGE->set_heading(get_string('createskill', 'tool_skills'));
    $PAGE->navbar->add(get_string('create', 'tool_skills'));
}



// Init form.
$form = new \tool_skills\form\skills_form(null, array('id' => $id));

// If the form was submitted.
if ($data = $form->get_data()) {
    // Handle form results.
    $menuid = \tool_skills\skills::manage_instance($data);

    // Redirect to skills list.
    redirect($listurl);

    // Otherwise if the form was cancelled.
} else if ($form->is_cancelled()) {
    // Redirect to skills list.
    redirect($listurl);
}

// If a menu ID is given.
if ($id !== null && $id > 0) {
    // Fetch the data for the menu.
    if ($record = \tool_skills\skills::get($id)->get_data()) {

        // Set the menu data to the menu edit form.
        $form->set_data($record);

        // If the menu is not available.
    } else {
        // Add a notification to the page.
        \core\notification::error(get_string('error:skillsnotfound', 'tool_skills'));

        // Redirect to menu list (where the notification is shown).
        redirect($listurl);
    }
}

// Start page output.
echo $OUTPUT->header();

// Show form.
echo $form->display();

// Finish page output.
echo $OUTPUT->footer();
