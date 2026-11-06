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
 * Tool Skills - Skills page of a course: the skills it awards and, for learners, their progress.
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
if (!\tool_skills\helper::can_view_course_page($context)) {
    throw new required_capability_exception($context, 'tool/skills:viewcourseskills', 'nopermissions', '');
}

$PAGE->set_url(new moodle_url('/admin/tool/skills/course.php', ['courseid' => $course->id]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(implode(moodle_page::TITLE_SEPARATOR, [get_string('skills', 'tool_skills'), format_string($course->shortname)]));
$PAGE->set_heading(format_string($course->fullname));

// Learners, as in the course completion report, see their own progress; everyone else what the course awards.
$islearner = is_enrolled($context, $USER, 'moodle/course:isincompletionreports', true);
$data = \tool_skills\helper::get_user_profile_skills_context($islearner ? $USER->id : 0, true, $course->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('skillsincourse', 'tool_skills'));

if (has_capability('tool/skills:managecourseskillslist', $context)) {
    echo html_writer::div($OUTPUT->single_button(
        new moodle_url('/admin/tool/skills/manage/courselist.php', ['courseid' => $course->id]),
        get_string('managecourseskills', 'tool_skills'),
        'get'
    ), 'mb-3');
}

if ($data['hasskills']) {
    echo html_writer::tag('p', get_string($islearner ? 'skillsincourse_learner' : 'skillsincourse_desc', 'tool_skills'));
    echo $OUTPUT->render_from_template('tool_skills/course_skills', $data);
} else {
    echo $OUTPUT->notification(get_string('noskillsincourse', 'tool_skills'), 'info', false);
}

echo $OUTPUT->footer();
