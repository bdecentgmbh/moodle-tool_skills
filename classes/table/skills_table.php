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
 * Tool skills - Skills list table currently in use.
 *
 * @package    tool_skills
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/tablelib.php');

use stdClass;
use moodle_url;
use core_course_category;
use core\output\notification;
use html_writer;

/**
 * Skills list table.
 */
class skills_table extends \table_sql {

    /**
     * Table contructor to define columns and headers.
     */
    public function __construct() {

        // Call parent constructor.
        parent::__construct('toolskills');

        // Define table headers and columns.
        $columns = ['identitykey', 'name', 'description', 'timecreated', 'categories', 'actions'];
        $headers = [
            get_string('key', 'tool_skills'),
            get_string('name', 'core'),
            get_string('description', 'core'),
            get_string('timecreated', 'core'),
            get_string('categories', 'core'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Remove sorting for some fields.
        $this->sortable(false);

        // Do not make the table collapsible.
        $this->collapsible(false);

        $this->set_attribute('id', 'tool_skills_list');
    }

    /**
     * Get the skills list.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $condition = 'archived != 1';

        // Filter the category.
        if ($this->filterset->has_filter('category')) {
            $values = $this->filterset->get_filter('category')->get_filter_values();
            $category = isset($values[0]) ? current($values) : '';
            $condition .= ' AND ' . $DB->sql_like('categories', ':value');
            $params = ['value' => '%"'.$category.'"%'];
        }

        // Set the query values to fetch skills.
        $this->set_sql('*', '{tool_skills}', $condition, $params ?? []);

        parent::query_db($pagesize, $useinitialsbar);
    }

    /**
     * Name of the skill column. Format the string to support multilingual.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_name(stdClass $row): string {
        return format_string($row->name);
    }

    /**
     * Description of the skill.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_description(stdClass $row): string {
        return format_text($row->description, FORMAT_HTML, ['overflowdiv' => false]);
    }

    /**
     * Categories list where this skill is available.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_categories(stdClass $row): string {

        $categories = $row->categories ?? [];
        if (empty($categories)) {
            return '';
        }

        $categories = json_decode($categories);
        $list = core_course_category::get_many($categories);
        $list = array_map(fn($cate) => $cate->get_formatted_name(), $list);

        return implode(', ', $list);
    }

    /**
     * Skill created time in user readable.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_timecreated(stdClass $row): string {
        return userdate($row->timecreated);
    }

    /**
     * Actions to manage the skill row. Like edit, change status, archive and delete.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_actions(stdClass $row): string {
        global $OUTPUT;

        // Base url to edit the skills.
        $baseurl = new \moodle_url('/admin/tool/skills/manage/edit.php', [
            'id' => $row->id,
            'sesskey' => \sesskey(),
        ]);

        // Skills List URL.
        $listurl = new \moodle_url('/admin/tool/skills/manage/list.php', [
            'id' => $row->id,
            'sesskey' => \sesskey(),
        ]);

        $actions = [];

        // Edit.
        $actions[] = [
            'url' => $baseurl,
            'icon' => new \pix_icon('t/edit', \get_string('edit')),
            'attributes' => ['class' => 'action-edit'],
        ];

        // Show/Hide.
        $checked = ($row->status) ? ['checked' => 'checked'] : [];
        $checkbox = html_writer::div(
            html_writer::empty_tag('input',
                ['type' => 'checkbox', 'class' => 'custom-control-input'] + $checked
            ) . html_writer::tag('span', '', ['class' => 'custom-control-label']),
            'custom-control custom-switch'
        );
        $statusurl = new \moodle_url($listurl, ['action' => ($row->status) ? 'disable' : 'enable']);
        $statusclass = ' toolskills-status-switch ';
        $statusclass .= $row->status ? 'action-hide' : 'action-show';
        $actions[] = html_writer::link($statusurl->out(false), $checkbox, ['class' => $statusclass]);

        // Archived.
        $actions[] = [
            'url' => new \moodle_url($listurl, ['t' => 'archive', 'action' => 'archive']),
            'icon' => new \pix_icon('f/archive', \get_string('archive', 'tool_skills'), 'tool_skills'),
            'attributes' => ['class' => 'action-archive'],
            'action' => new \confirm_action(get_string('archiveskill', 'tool_skills')),
        ];

        $actionshtml = [];
        foreach ($actions as $action) {
            if (!is_array($action)) {
                $actionshtml[] = $action;
                continue;
            }
            $action['attributes']['role'] = 'button';
            $actionshtml[] = $OUTPUT->action_icon(
                $action['url'],
                $action['icon'],
                ($action['action'] ?? null),
                $action['attributes'],
            );
        }
        return html_writer::div(join('', $actionshtml), 'skill-item-actions item-actions mr-0');
    }

    /**
     * Create a navbar switch for toggling editing mode.
     * @param stdclass $row
     * @return string Html containing the edit switch
     */
    public function edit_switch($row) {
        global $PAGE, $OUTPUT;

        $temp = (object) [
            'legacyseturl' => (new moodle_url('/admin/tool/skills/manage/list.php', [
                'id' => $row->id,
                'sesskey' => sesskey(),
                ]))->out(false),
            'pagecontextid' => $PAGE->context->id,
            'pageurl' => $PAGE->url,
            'sesskey' => sesskey(),
            'checked' => $row->status,
            'id' => $row->id,
        ];
        return $OUTPUT->render_from_template('tool_skills/status_switch', $temp);
    }

    /**
     * Override the default "Nothing to display" message when no skills are available.
     *
     * @return void
     */
    public function print_nothing_to_display() {
        global $OUTPUT;

        // Show notification as html element.
        $notification = new notification(get_string('skillsnothingtodisplay', 'tool_skills'), notification::NOTIFY_INFO);
        $notification->set_show_closebutton(false); // No close button for this notification.

        echo $OUTPUT->render($notification); // Print the notification on page.
    }
}
