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
 * Tool Skills - Courses for skills: the courses that award skills, for learners to find courses.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

require_login(null, false);
$context = context_system::instance();
if (!\tool_skills\helper::can_browse_courses()) {
    throw new required_capability_exception($context, 'tool/skills:browsecourses', 'nopermissions', '');
}

// All skills, or the one chosen.
$skills = \tool_skills\helper::get_course_skills(0);
$skillid = optional_param('skill', 0, PARAM_INT);
if (!isset($skills[$skillid])) {
    $skillid = 0;
}

$url = new moodle_url('/admin/tool/skills/catalog.php', $skillid ? ['skill' => $skillid] : []);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('catalog', 'tool_skills'));
$PAGE->set_heading(get_string('catalog', 'tool_skills'));

$table = new \tool_skills\table\catalog_table($skillid);
$table->define_baseurl($url);

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('catalog_desc', 'tool_skills'));

$select = new single_select(
    new moodle_url('/admin/tool/skills/catalog.php'),
    'skill',
    [0 => get_string('allskills', 'tool_skills')] + $skills,
    $skillid,
    null
);
$select->set_label(get_string('skill', 'tool_skills'));
echo html_writer::div($OUTPUT->render($select), 'mb-3');

$table->out(50, false);

echo $OUTPUT->footer();
