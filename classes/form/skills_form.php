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
 * Tool skills - Skill create moodle form
 *
 * @package    tool_skills
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills\form;

defined('MOODLE_INTERNAL') || die();

// Require forms library.
require_once($CFG->libdir.'/formslib.php');

use context_system;
use html_writer;
use \tool_skills\skills;

/**
 * Skills create/edit form.
 */
class skills_form extends \moodleform {

    /**
     * Menu item create form elements defined.
     *
     * @return void
     */
    public function definition() {
        global $DB, $PAGE, $CFG;

        $mform = $this->_form;

        // Current skill id to edit.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        require_once($CFG->dirroot.'/admin/tool/skills/form/element-colorpicker.php');
        \MoodleQuickForm::registerElementType(
            'tool_skills_colorpicker',
            $CFG->dirroot.'/admin/tool/skills/form/element-colorpicker.php',
            'moodlequickform_toolskills_colorpicker'
        );

        // General section.
        $mform->addElement('header', 'general', get_string('general', 'core'));

        // Skill name.
        $mform->addElement('text', 'name', get_string('skilltitle', 'tool_skills'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');
        $mform->addHelpButton('name', 'skilltitle', 'tool_skills');

        // Skill identity key element.
        $mform->addElement('text', 'identitykey', get_string('identitykey', 'tool_skills'), ['size' => '50']);
        $mform->addRule('identitykey', null, 'required', null, 'client');
        $mform->setType('identitykey', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('identitykey', 'identitykey', 'tool_skills');

        // Add the internal description element.
        $mform->addElement('textarea', 'description', get_string('description'), ['size' => '50']);
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'description', 'tool_skills');

        // Add the status element.
        $statusoptions = [
            skills::STATUS_ENABLE => get_string('enabled', 'tool_skills'),
            skills::STATUS_DISABLE => get_string('disabled', 'tool_skills'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'tool_skills'), $statusoptions);
        $mform->addHelpButton('status', 'status', 'tool_skills');

        // Expected learning time for this skill.
        $mform->addElement('duration', 'learningtime', get_string('learningtime', 'tool_skills'), [
            'optional' => true,
            'defaultunit' => DAYSECS,
        ]);
        $mform->setDefault('learningtime', 90 * DAYSECS);
        $mform->addHelpButton('learningtime', 'learningtime', 'tool_skills');

        // Skill color element.
        $mform->addElement('tool_skills_colorpicker', 'color', get_string('skillcolor', 'tool_skills'));
        $mform->addHelpButton('color', 'skillcolor', 'tool_skills');
        $mform->setType('color', PARAM_TEXT);

        // Add the Available in Course Categories element.
        $categories = \core_course_category::make_categories_list();
        $cate = $mform->addElement('autocomplete', 'categories', get_string('availableincoursecategories', 'tool_skills'),
                $categories);
        $cate->setMultiple(true);
        $mform->addHelpButton('categories', 'availableincoursecategories', 'tool_skills');

        // Levels setup for this skills.
        $mform->addElement('header', 'skilllevels', get_string('skillslevels', 'tool_skills'));

        // Levels count selection element.
        $options = array_combine(range(0, 10), range(0, 10));
        $mform->addElement('select', 'levelscount', get_string('levelscount', 'tool_skills'), $options);
        $mform->addHelpButton('levelscount', 'levelscount', 'tool_skills');

        $mform->registerNoSubmitButton('updatelevelscount');
        $mform->addElement('submit', 'updatelevelscount', get_string('updatelevelscount', 'tool_skills'), [
            'class' => 'd-none',
        ]);

        $PAGE->requires->js_amd_inline("
            document.querySelector('select[name=levelscount]') !== null ? document.querySelector('select[name=levelscount]')
                .onchange = (e) => document.querySelector('input[name=updatelevelscount]').click() : ''; "
        );
    }


    /**
     * Definied the levels form fields to attach with form after the forms are defined,
     * Levels are created based on the number of levels.
     *
     * @return void
     */
    public function definition_after_data() {
        $mform = $this->_form;

        $levelscount = $mform->getElementValue('levelscount');
        $levelscount = !empty($levelscount) ? reset($levelscount) : 0;

        for ($i = 0; $i <= $levelscount; $i++) {

            // Static heading.
            $mform->addElement('static', "level[$i]", html_writer::tag('h5', get_string('levelsnohead', 'tool_skills', $i)));

            $mform->addElement('hidden', "levels[$i][id]");
            $mform->setType("levels[$i][id]", PARAM_INT);

            // Level name.
            $mform->addElement('text', "levels[$i][name]", get_string('levelsname', 'tool_skills', $i), '');
            $mform->setType("levels[$i][name]", PARAM_TEXT);
            $mform->addRule("levels[$i][name]", get_string('required'), 'required', '', 'client');
            $mform->addHelpButton("levels[$i][name]", 'levelsname', 'tool_skills');

            // Level points.
            $mform->addElement('text', "levels[$i][points]", get_string('levelspoint', 'tool_skills', $i), '');
            $mform->setType("levels[$i][points]", PARAM_INT);
            $mform->addRule("levels[$i][points]", get_string('required'), 'required', '', 'client');
            $mform->addRule("levels[$i][points]", get_string('error:numeric', 'tool_skills'), 'numeric', '', 'client');
            $mform->addHelpButton("levels[$i][points]", 'levelspoint', 'tool_skills');

            // Set the default point for this level.
            if ($mform->getElementValue("levels[$i][points]") === null) {
                $leveldefaultpoint = $i * 10; // Find the default point.
                $mform->setDefault("levels[$i][points]", $leveldefaultpoint);
            }
            // Level color.
            $mform->addElement('tool_skills_colorpicker', "levels[$i][color]", get_string('levelscolor', 'tool_skills', $i), '');
            $mform->setType("levels[$i][color]", PARAM_TEXT);
            $mform->addHelpButton("levels[$i][color]", 'levelscolor', 'tool_skills');

            // Level image.
            $mform->addElement('filemanager', "levels[$i][image]", get_string('levelsimage', 'tool_skills', $i));
            $mform->addHelpButton("levels[$i][image]", 'levelsimage', 'tool_skills');

            // Set the default values for the level 0.
            if ($i == 0  && !$mform->getElementValue("levels[$i][name]")) {
                $mform->setDefaults([
                    "levels[$i][name]" => get_string('skillslevel', 'tool_skills') . ' ' . $i,
                    "levels[$i][points]" => '0'
                ]);
            }

        }
        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Load in existing data as form defaults. Usually new entry defaults are stored directly in
     * form definition (new entry form); this function is used to load in data where values
     * already exist and data is being edited (edit entry form).
     *
     * note: $slashed param removed
     *
     * @param stdClass|array $defaultvalues object or array of default values
     */
    public function set_data($defaultvalues) {

        $this->data_preprocessing($defaultvalues); // Include to store the files.

        parent::set_data($defaultvalues);
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * Do not override this method, override data_postprocessing() instead.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $this->data_postprocessing($data);
        }
        return $data;
    }

    /**
     * Process the skills module data before set the default.
     *
     * @param  mixed $defaultvalues default values
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        // System context.
        $context = context_system::instance();

        // Convert to object, file manager methods require the objects.
        $defaultvalues = (object) $defaultvalues;

        $filemanagers = [
            'image' => 'image'
        ];

        // Levels count.
        $levelscount = $defaultvalues->levelscount;

        // Prepare the file manager fields to store images.
        foreach ($filemanagers as $configname => $filearea) {
            // For all levels in this skills.
            for ($i = 1; $i <= $levelscount; $i++) {

                if (empty($defaultvalues->levels[$i])) {
                    continue;
                }
                // Fileare for this level.
                $filearea .= '_' . $i;
                // Draft item id.
                $draftitemid = file_get_submitted_draft_itemid($filearea);
                // Use the level id as item id.
                $levelid = $defaultvalues->levels[$i]['id'] ?? 0;
                // Store the draft files to area files.
                file_prepare_draft_area(
                    $draftitemid, $context->id, 'tool_skills', $filearea, $levelid, [
                        'subdirs' => 0,
                        'accepted_types' => ['web_image'],
                    ]
                );
                $defaultvalues->levels[$i][$configname] = $draftitemid;
            }
        }
    }

    /**
     * Prepare the data after form was submited.
     *
     * Store all the editor files and update the structure and the file urls with placeholders.
     * It used the ludemy block instance id (ludemyid) as item id and uses the local_ludemy as component.
     * Also use the name of the editor as filearea.
     *
     * @param  mixed $data submitted data
     * @return void
     */
    public function data_postprocessing(&$data) {

        $context = context_system::instance();
        // Prepare the editor to support files.
        $data = (object) $data;

        $filemanagers = [
            'image' => 'image'
        ];

        $levelscount = $data->levelscount;

        // Prepare the file manager fields to store images.
        foreach ($filemanagers as $configname => $filearea) {

            for ($i = 1; $i <= $levelscount; $i++) {

                if (empty($data->levels[$i])) {
                    continue;
                }

                $levelid = $data->levels[$i]['id'] ?: 0;
                // Now save the files in correct part of the File API.
                $filearea .= '_' . $i;

                file_save_draft_area_files(
                    $data->levels[$i][$configname], $context->id, 'tool_skills',
                    $filearea, $levelid, $this->get_editor_options($context)
                );
            }
        }
    }

    /**
     * Editor form element options.
     *
     * @param context $context
     * @return array
     */
    protected function get_editor_options($context=null) {
        global $PAGE;

        return [
            'subdirs' => true,
            'maxfiles' => 1,
            'maxbytes' => 1000000,
            'context' => $context ?: $PAGE->context
        ];
    }

    /**
     * Validate the user input data. Verified the URL input filled if the item type is static.
     *
     * @param array $data
     * @param array $files
     * @return void
     */
    public function validation($data, $files) {
        global $DB;

        $errors = []; // Empty errors list.

        if ($data['identitykey']) {
            // Get the records with same identity key.
            if ($records = $DB->get_records('tool_skills', ['identitykey' => $data['identitykey']])) {

                if (empty($data['id'])) {
                    $errors['identitykey'] = get_string('error:identityexists', 'tool_skills');
                } else {
                    foreach ($records as $record) {
                        if ($record->id != $data['id']) {
                            $errors['identitykey'] = get_string('error:identityexists', 'tool_skills');
                        }
                    }
                }
            }
        }

        return $errors ?? [];
    }
}
