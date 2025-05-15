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
 * Tool skills - Users skill points report.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\table;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

use core_table\dynamic as dynamic_table;
use core_table\local\filter\filterset;

require_once($CFG->libdir.'/tablelib.php');

/**
 * View the users skills for this skill.
 */
class users_skills extends \table_sql implements dynamic_table {

    /**
     * Current skill instance record data.
     *
     * @var stdclass
     */
    public $skill;

    /**
     * Fetch completions users list.
     *
     * @param  int $skillid
     * @return void
     */
    public function __construct($skillid) {
        global $PAGE, $DB;
        parent::__construct($skillid);

        // Define the headers and columns.
        $headers = [];
        $columns = [];
        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $headers[] = get_string('pointsearned', 'tool_skills');
        $columns[] = 'points';

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Remove sorting for some fields.
        $this->sortable(false);

        // Do not make the table collapsible.
        $this->collapsible(false);

        $this->set_attribute('id', 'tool_skills_users_report');

        $this->skill = $DB->get_record('tool_skills', ['id' => $skillid] );

    }

    /**
     * Get the context of this table.
     *
     * @return \context
     */
    public function get_context(): \context {

        return \context_system::instance();
    }

    /**
     * Guess the base url for the participants table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new \moodle_url('/admin/tools/skills/manage/usersreport.php', ['id' => $this->skill->id]);
    }

    /**
     * Query the database for results to display in the table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {

        // Set the query values to fetch skills.
        $select = 'usp.*, s.*, u.*';

        $from = '{tool_skills_userpoints} usp
        LEFT JOIN {tool_skills} s ON usp.skill = s.id
        LEFT JOIN {user} u ON usp.userid = u.id';

        $this->set_sql($select, $from, 's.id = :skillid', ['skillid' => $this->skill->id]);

        parent::query_db($pagesize, $useinitialsbar);
    }

    /**
     * Generate the fullname column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_fullname($data) {
        global $OUTPUT;

        return $OUTPUT->user_picture($data, ['size' => 35, 'includefullname' => true]);
    }

    /**
     * Verify the user has the capability to view the table.
     *
     * @return bool
     */
    public function has_capability(): bool {
        return has_capability('tool/skills:viewotherspoints', $this->get_context());
    }
}
