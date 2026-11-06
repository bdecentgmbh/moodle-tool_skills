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
 * Tool Skills - Participants' progress of a course, one skill at a time.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
if (!\tool_skills\helper::can_view_course_participants($context)) {
    throw new required_capability_exception($context, 'tool/skills:viewcourseparticipants', 'nopermissions', '');
}

// One skill at a time, the first one by default.
$skills = \tool_skills\helper::get_course_skills($course->id);
$skillid = optional_param('skill', 0, PARAM_INT);
if (!isset($skills[$skillid])) {
    $skillid = (int) array_key_first($skills);
}

$url = new moodle_url('/admin/tool/skills/progress.php', ['courseid' => $course->id, 'skill' => $skillid]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(implode(moodle_page::TITLE_SEPARATOR, [
    get_string('participantsprogress', 'tool_skills'),
    format_string($course->shortname),
]));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_secondary_active_tab('manage-tool-skills');

// The participants of the groups the user may see, as on the Participants page.
$groupid = (int) groups_get_course_group($course, true);
$nogroup = !$groupid && groups_get_course_groupmode($course) == SEPARATEGROUPS
    && !has_capability('moodle/site:accessallgroups', $context);

$table = null;
if ($skillid && !$nogroup) {
    $table = new \tool_skills\table\course_participants_table(
        $context,
        \tool_skills\skills::get($skillid),
        $groupid,
        has_capability('tool/skills:viewcourseparticipants', $context)
    );
    $table->define_baseurl($url);
    $table->is_downloadable(true);
    $table->show_download_buttons_at([TABLE_P_BOTTOM]);
    $filename = clean_filename($course->shortname . ' ' . $skills[$skillid]);
    if ($table->is_downloading(optional_param('download', '', PARAM_ALPHA), $filename, $skills[$skillid])) {
        $table->out(50, false);
        die();
    }
}

echo $OUTPUT->header();
echo \tool_skills\helper::get_course_navigation($context, 'progress');
echo $OUTPUT->heading(get_string('participantsprogress', 'tool_skills'));

if (!$skillid) {
    echo $OUTPUT->notification(get_string('noskillsincourse', 'tool_skills'), 'info', false);
} else if ($nogroup) {
    echo $OUTPUT->notification(get_string('notingroup'), 'info', false);
} else {
    $selecturl = new moodle_url('/admin/tool/skills/progress.php', ['courseid' => $course->id]);
    $select = new single_select($selecturl, 'skill', $skills, $skillid, null);
    $select->set_label(get_string('skill', 'tool_skills'));
    echo html_writer::div(
        $OUTPUT->render($select) . groups_print_course_menu($course, $url, true),
        'd-flex flex-wrap align-items-center gap-3 mb-3'
    );
    $table->out(50, true);
}

echo $OUTPUT->footer();
