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

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

use tool_skills\points_history;
use tool_skills\skills;

/**
 * Tool skills - Participants' progress of a course in one skill.
 *
 * One row per learner (active enrolment with moodle/course:isincompletionreports, as in the course
 * completion report) of the course or the given group, with the points from this course and, when the
 * user may see them, the level and the total points, which include points from other courses.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_participants_table extends \table_sql {
    /** @var skills The skill. */
    protected skills $skill;

    /**
     * Set up the columns and the query.
     *
     * @param \context_course $context The course.
     * @param skills $skill The skill.
     * @param int $groupid Only the members of this group, 0 for all.
     * @param bool $showtotals Whether to show the level and the total points.
     */
    public function __construct(\context_course $context, skills $skill, int $groupid, bool $showtotals) {
        parent::__construct('tool_skills_course_participants');
        $this->skill = $skill;

        $columns = ['fullname' => get_string('fullname')];
        if ($showtotals) {
            $columns['level'] = get_string('skillslevel', 'tool_skills');
            $columns['points'] = get_string('pointsearned', 'tool_skills');
        }
        $columns['coursepoints'] = get_string('coursepoints', 'tool_skills');
        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));
        $this->sortable(true, 'lastname');
        $this->no_sorting('level');
        $this->collapsible(false);
        $this->initialbars(true);
        $this->set_attribute('id', 'tool_skills_course_participants');

        $enrolled = get_enrolled_with_capabilities_join($context, '', 'moodle/course:isincompletionreports', $groupid, true);
        // The user fields start with u.id, so rows are keyed by user.
        $userfields = \core_user\fields::for_userpic()->get_sql('u', false);
        $this->set_sql(
            ltrim($userfields->selects, ', ') . ', COALESCE(up.points, 0) AS points, COALESCE(cp.coursepoints, 0) AS coursepoints',
            "{user} u {$enrolled->joins} {$userfields->joins}
             LEFT JOIN {tool_skills_userpoints} up ON up.userid = u.id AND up.skill = :skill
             LEFT JOIN (SELECT userid, SUM(points) AS coursepoints
                          FROM {" . points_history::TABLE . "}
                         WHERE skill = :historyskill AND courseid = :courseid
                      GROUP BY userid) cp ON cp.userid = u.id",
            $enrolled->wheres,
            ['skill' => $skill->get_id(), 'historyskill' => $skill->get_id(), 'courseid' => $context->instanceid]
                + $enrolled->params + $userfields->params
        );
    }

    /**
     * The participant, with picture and link to the profile; the name only in downloads.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_fullname($row) {
        global $OUTPUT;
        if ($this->is_downloading()) {
            return fullname($row);
        }
        return $OUTPUT->user_picture($row, ['size' => 35, 'includefullname' => true]);
    }

    /**
     * The level the participant reached with their total points.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_level($row) {
        $levelid = $this->skill->get_level_for_points((int) $row->points);
        return $levelid ? format_string($this->skill->get_levels()[$levelid]->name) : '';
    }
}
