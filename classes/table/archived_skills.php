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

use core_course_category;
use stdClass;

/**
 * Skills list table.
 */
class archived_skills extends \table_sql {

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
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Remove sorting for some fields.
        $this->sortable(false);

        // Do not make the table collapsible.
        $this->collapsible(false);

        $this->set_attribute('id', 'tool_skills_archived_list');
    }

    /**
     * Get the skills list.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {

        // Set the query values to fetch skills.
        $this->set_sql('*', '{tool_skills}', 'archived = 1');

        parent::query_db($pagesize, $useinitialsbar);
    }

    /**
     * Name of the skill column. Format the string to support multilingual.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_name(stdClass $row) : string {
        return format_string($row->name);
    }

    /**
     * Description of the skill.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_description(stdClass $row) : string {
        return format_text($row->description, FORMAT_HTML, ['overflow' => false]);
    }

    /**
     * Categories list where this skill is available.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_categories(stdClass $row) : string {

        $categories = $row->categories ?? [];
        if (empty($categories)) {
            return '';
        }

        $categories = json_decode($categories);
        $list = core_course_category::get_many($categories);
        // $list = array_map(fn(&$cate) => $cate->get_formatted_name(), $list);

        array_walk($list, function(&$cate) {
            $cate = $cate->get_formatted_name();
        });

        return implode(', ', $list);
    }

    /**
     * Skill created time in user readable.
     *
     * @param stdClass $row
     * @return string
     */
    public function timecreated(stdClass $row) :string {
        return usertime($row->timecreated);
    }

    /**
     * Actions to manage the skill row. Like edit, change status, archive and delete.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_actions(stdClass $row) : string {
        return '';
    }
}
