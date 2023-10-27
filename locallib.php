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
 * Tool skills - Commonly used skill methods.
 *
 * @package   tool_skills
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Filter form for the templates table.
 */
class tool_skills_table_filter extends \moodleform {

    /**
     * Filter form elements defined.
     *
     * @return void
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('html', html_writer::tag('h3', get_string('filter')));
        $list = [0 => get_string('all')] + core_course_category::make_categories_list();
        $mform->addElement('autocomplete', 'category', get_string('category'), $list);

        $mform->addElement('hidden', 't', $this->_customdata['t'] ?? 'active');
        $mform->setType('t', PARAM_ALPHA);

        $this->add_action_buttons(false, get_string('filter'));
    }
}
