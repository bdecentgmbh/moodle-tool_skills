<?php


namespace tool_skills\form;

use tool_skills\skills;
use tool_skills\courseskills;
use moodle_url;
use stdClass;

require_once($CFG->libdir.'/formslib.php');

class course_form extends \core_form\dynamic_form {

    /**
     * Defined the fields for the skill.
     *
     * @return void
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $skill = $this->optional_param('skill', 0, PARAM_INT);
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);

        // $skillcourseid = $this->optional_param('id', 0, PARAM_INT);
        // Skill id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Skill id.
        $mform->addElement('hidden', 'skill', $skill);
        $mform->setType('skill', PARAM_INT);

        // Courseid hidden field
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // Add the status element.
        $statusoptions = [
            skills::STATUS_DISABLE => get_string('disabled', 'tool_skills'),
            skills::STATUS_ENABLE => get_string('enabled', 'tool_skills'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'tool_skills'), $statusoptions);
        $mform->addHelpButton('status', 'status', 'tool_skills');

        // Upon completion.
        $options = [
            skills::COMPLETIONNOTHING => get_string('completionnothing', 'tool_skills'),
            skills::COMPLETIONPOINTS => get_string('completionpoints', 'tool_skills'),
            skills::COMPLETIONSETLEVEL => get_string('completionsetlevel', 'tool_skills'),
            skills::COMPLETIONFORCELEVEL => get_string('completionforcelevel', 'tool_skills'),
        ];
        $mform->addElement('select', 'uponcompletion', get_string('uponcompletion', 'tool_skills'), $options);

        // Completion points element.
        $mform->addElement('text', 'points', get_string('completionpoints', 'tool_skills'));
        $mform->hideIf('points', 'uponcompletion', 'neq', skills::COMPLETIONPOINTS);

        // Completion level.
        $skill = $this->optional_param('skill', 0, PARAM_INT);
        $levels = $DB->get_records_menu('tool_skills_levels', ['skill' => $skill], '', 'id, name');

        // List of levels to complete.
        $mform->addElement('select', 'level', get_string('completionlevel', 'tool_skills'), $levels);
        $mform->hideIf('level', 'uponcompletion', 'in', [skills::COMPLETIONNOTHING, skills::COMPLETIONPOINTS]);

    }

    /**
     * Get the context of this form used.
     *
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        // Course record id.
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);

        return $courseid ? \context_course::instance($courseid) : \context_system::instance();
    }

    /**
     * Check the access for the submit data to this form.
     *
     * @return bool
     */
    protected function check_access_for_dynamic_submission(): void {
        // TODO: Validatation of user capability goes here.
    }

    /**
     * Process the submission from AJAX.
     *
     * @return void
     */
    public function process_dynamic_submission() {
        global $DB;

        // Get the submitted content data.
        $record = (object) $this->get_data();

        // $courseid = $this->optional_param('courseid', 0, PARAM_INT);

        if (isset($record->id) && $DB->record_exists('tool_skills_courses', ['id' => $record->id])) {
            // Level id to update.
            $skillcourseid = $record->id;
            // Time modified the level.
            $record->timemodified = time();
            // Update the level record.
            $DB->update_record('tool_skills_courses', $record);
        } else {
            // New record add the created time.
            $record->timecreated = time();
            // Insert the record of the new skill.
            $skillcourseid = $DB->insert_record('tool_skills_courses', $record);
        }

        courseskills::get($record->courseid)->manage_users_completion();

        return true;
    }

    /**
     * Set the data to form. Need to call this method on direct data setup.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $skillid = $this->optional_param('skill', 0, PARAM_INT);

        $defaults = [
            'courseid' => $courseid,
            'skill' => $skillid,
        ];

        if ($skillid && $courseid) {
            $record = (array) $DB->get_record('tool_skills_courses', ['skill' => $skillid, 'courseid' => $courseid]);
            $defaults += $record;
        }

        // Setup the block config data to form.
        $this->set_data($defaults);
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/admin/tool/skills/manage/courselist.php',
            ['courseid' => $this->optional_param('courseid', 0, PARAM_INT)]);
    }

    /**
     * Update the status of the skill in course.
     *
     * @param int $skillid
     * @param int $courseid
     * @param bool $status
     * @return void
     */
    public static function update_status(int $skillid, int $courseid, bool $status) : void {
        global $DB;

        $record = new stdClass;
        $record->skill = $skillid;
        $record->courseid = $courseid;

        if ($result = $DB->get_record('tool_skills_courses', (array) $record)) {
            $result->status = $status;
            $DB->update_record('tool_skills_courses', $result);
        } else {
            $record->status = $status;
            $DB->insert_record('tool_skills_courses', $record);
        }
    }

}
