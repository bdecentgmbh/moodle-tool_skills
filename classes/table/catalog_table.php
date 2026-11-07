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

namespace tool_skills\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

use html_writer;
use tool_skills\helper;

/**
 * Tool skills - The courses that award skills, on the Courses for skills page.
 *
 * One row per course and skill, for the skills enabled in a course and enabled and not archived site-wide,
 * in the courses the user sees in Moodle's course listings, when the course or its activities award
 * something. Each row says what the course awards and whether the user has completed the course, is
 * enrolled in it, or how to enrol.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalog_table extends \table_sql {
    /** @var int[] The courses the user has completed. */
    protected array $completed;

    /**
     * Set up the columns and the query.
     *
     * @param int $skillid Only the courses of this skill, 0 for all skills.
     */
    public function __construct(int $skillid) {
        global $DB, $USER;
        parent::__construct('tool_skills_catalog');

        $columns = [
            'coursename' => get_string('course'),
            'skillname' => get_string('skill', 'tool_skills'),
            'awards' => get_string('awards', 'tool_skills'),
            'status' => get_string('status', 'tool_skills'),
        ];
        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));
        $this->sortable(true, 'coursename');
        $this->no_sorting('awards');
        $this->no_sorting('status');
        $this->collapsible(false);
        $this->set_attribute('id', 'tool_skills_catalog');

        $where = 'sc.status = 1 AND s.status = 1 AND s.archived = 0';
        $params = [];
        if ($skillid) {
            $where .= ' AND s.id = :skill';
            $params['skill'] = $skillid;
        }
        $this->set_sql(
            'sc.id, sc.courseid, sc.uponcompletion, sc.points, sc.level, s.id AS skillid, s.name AS skillname,
             c.fullname AS coursename, c.visible, c.category',
            '{tool_skills_courses} sc
             JOIN {tool_skills} s ON s.id = sc.skill
             JOIN {course} c ON c.id = sc.courseid',
            $where,
            $params
        );

        $this->completed = $DB->get_fieldset_select(
            'course_completions',
            'course',
            'userid = ? AND timecompleted > 0',
            [$USER->id]
        );
    }

    /**
     * Fetch the rows of the page: the courses the user may see, as Moodle's course listings do, that award
     * something in the skill.
     *
     * The visibility of a course is a capability check, and the addons tell their awards, so the rows are
     * filtered and paged here.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $sort = $this->get_sql_sort();
        $rows = $DB->get_records_sql(
            "SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}
             ORDER BY " . ($sort ? "$sort, " : '') . 'sc.id',
            $this->sql->params
        );
        $rows = array_filter($rows, fn($row) => \core_course_category::can_view_course_info(
            (object) ['id' => $row->courseid, 'visible' => $row->visible, 'category' => $row->category]
        ) && ((int) $row->uponcompletion || $this->get_awards($row)));
        $this->pagesize($pagesize, count($rows));
        $this->rawdata = array_slice($rows, $this->get_page_start(), $this->get_page_size(), true);
    }

    /**
     * What the course awards in the skill: on its completion, as on the course's Skills page, and from the addons.
     *
     * @param \stdClass $row
     * @return string[]
     */
    protected function get_awards(\stdClass $row): array {
        if (!isset($row->awards)) {
            $row->awards = [];
            $completion = helper::get_award_label((int) $row->uponcompletion, (int) $row->points, (int) $row->level);
            if ($completion !== '') {
                $row->awards[] = get_string('coursecompletion') . ' · ' . $completion;
            }
            $addons = helper::extend_addons_get_course_awards((int) $row->courseid, (int) $row->skillid);
            $row->awards = array_merge($row->awards, $addons);
        }
        return $row->awards;
    }

    /**
     * The course, linked; dimmed when hidden, as in the course listings.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursename($row) {
        $context = \context_course::instance($row->courseid);
        return html_writer::link(
            new \moodle_url('/course/view.php', ['id' => $row->courseid]),
            format_string($row->coursename, true, ['context' => $context]),
            $row->visible ? [] : ['class' => 'dimmed']
        );
    }

    /**
     * The skill.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_skillname($row) {
        return format_string($row->skillname);
    }

    /**
     * What the course awards in the skill.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_awards($row) {
        return html_writer::alist($this->get_awards($row), ['class' => 'list-unstyled mb-0']);
    }

    /**
     * Whether the user has completed the course or is enrolled in it; otherwise how to enrol, as in the course listings.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_status($row) {
        global $OUTPUT;

        if (in_array($row->courseid, $this->completed)) {
            return get_string('completed');
        }
        if (is_enrolled(\context_course::instance($row->courseid), null, '', true)) {
            return get_string('enrolled', 'tool_skills');
        }
        return implode('', array_map(
            fn($icon) => $OUTPUT->render($icon),
            enrol_get_course_info_icons((object) ['id' => $row->courseid])
        ));
    }
}
