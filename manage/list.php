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
 * Tool Skills - skills overview page
 *
 * @package    tool_skills
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require config.
require(__DIR__.'/../../../../config.php');

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');


// Get parameters.
$action = optional_param('action', null, PARAM_ALPHAEXT);
$skillid = optional_param('id', null, PARAM_INT);
$tab = optional_param('t', 'active', PARAM_ALPHA);

// Get system context.
$context = context_system::instance();

// Access checks.
require_login();
require_capability('tool/skills:manage', $context);

// Create a page URL.
$urlparams = [];
$urlparams = ($tab == 'archive') ? ['t' => 'archive'] : [];
$pageurl = new moodle_url('/admin/tool/skills/manage/list.php', $urlparams);

// Prepare the page (to make sure that all necessary information is already set even if we just handle the actions as a start).
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_cacheable(false);

// Further prepare the page.
$PAGE->set_heading(get_string('skillslisthead', 'tool_skills'));

// Setup the breadcrumb.
$PAGE->navbar->add(get_string('tools', 'admin'), new moodle_url('/admin/category.php', array('category' => 'tool')));
$PAGE->navbar->add(get_string('pluginname', 'tool_skills'), $pageurl);

// Process actions.
if ($action !== null && confirm_sesskey()) {
    // Every action is based on a skill, thus the skill ID param has to exist.
    $skillid = required_param('id', PARAM_INT);

    // Create skill instance. Actions are performed in skills instance.
    $skill = tool_skills\skills::get($skillid);

    // Start the query transaction snapshots.
    $transaction = $DB->start_delegated_transaction();

    // Perform the requested action.
    switch ($action) {
        // Triggered action is delete, then init the deletion of skill and levels.
        case 'delete':
            // Delete the skill.
            if ($skill->delete_skill()) {
                // Notification to user for skill deleted success.
                \core\notification::success(get_string('skillsdeleted', 'tool_skills'));
            }
            break;
        case 'archive':
            // Delete the skill.
            if ($skill->archive_skill()) {
                // Notification to user for skill deleted success.
                \core\notification::success(get_string('skillsarchived', 'tool_skills'));
            }
            break;
        case 'active':
            // Delete the skill.
            if ($skill->active_skill()) {
                // Notification to user for skill deleted success.
                \core\notification::success(get_string('skillsactivated', 'tool_skills'));
            }
            break;
        case "copy":
            // Duplicate the skill and it levels.
            $skill->duplicate();
            break;
        case "disable":
            // Disable the skill.
            $skill->update_status(false);
            break;
        case "enable":
            // Enable the skill.
            $skill->update_status(true);
            break;
    }

    // Allow to update the changes to database.
    $transaction->allow_commit();

    // Redirect to the same page.
    redirect($pageurl);
}

// Further prepare the page.
$PAGE->set_title(get_string('skillslist', 'tool_skills'));

// Build skills table.
$filterset = new tool_skills\table\skills_filterset;

if ($categoryid = optional_param('category', null, PARAM_INT)) {
    $category = new \core_table\local\filter\integer_filter('category');
    $category->add_filter_value($categoryid);
    $filterset->add_filter($category);
    $filtered = true;
}

if ($tab == 'archive') {
    $table = new \tool_skills\table\archived_skills($context->id);
} else {
    $table = new \tool_skills\table\skills_table($context->id);
}

$table->define_baseurl($pageurl);
$table->set_filterset($filterset);

// Header.
echo $OUTPUT->header();

// Skills description.
echo get_string('skillslist_desc', 'tool_skills');

// Table Tabs.
$tabs = [];
// Active skills table tab.
$tabs[] = new tabobject('active',
    new moodle_url($PAGE->url, ['t' => 'active']), get_string('activeskills', 'tool_skills'), '', true);
// Archive skills table tab.
$tabs[] = new tabobject('archive',
    new moodle_url($PAGE->url, ['t' => 'archive']), get_string('archiveskills', 'tool_skills'), '', true);

// Create skills button to create new skill.
$createbutton = $OUTPUT->box_start();
$createbutton .= \tool_skills\helper::skills_buttons($tab, $filtered ?? false);
$createbutton .= $OUTPUT->box_end();

echo $createbutton;

// Tab tree.
echo $OUTPUT->tabtree($tabs, $tab);

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

    // Filter form display.
    var filterIcon = document.querySelector("#tool-skills-filter");
    var filterForm = document.querySelector("#tool-skills-filterform");
    filterIcon.onclick = (e) => filterForm.classList.toggle("hide");

})');


// Footer.
echo $OUTPUT->footer();
