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
require_once($CFG->libdir . '/formslib.php');

use html_writer;
use context_system;
use tool_skills\skills;

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

        // Register the custom color picker form element.
        require_once($CFG->dirroot . '/admin/tool/skills/form/element-colorpicker.php');
        \MoodleQuickForm::registerElementType(
            'tool_skills_colorpicker',
            $CFG->dirroot . '/admin/tool/skills/form/element-colorpicker.php',
            'moodlequickform_toolskills_colorpicker'
        );

        // Current skill id to edit.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

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

        // Add the Available in Course Categories element.
        $categories = \core_course_category::make_categories_list();
        $cate = $mform->addElement(
            'autocomplete',
            'categories',
            get_string('availableincoursecategories', 'tool_skills'),
            $categories
        );
        $cate->setMultiple(true);
        $mform->addHelpButton('categories', 'availableincoursecategories', 'tool_skills');

        // Skill color element.
        $mform->addElement('tool_skills_colorpicker', 'color', get_string('skillcolor', 'tool_skills'));
        $mform->setType('color', PARAM_TEXT);
        $mform->addHelpButton('color', 'skillcolor', 'tool_skills');

        // Levels setup for this skills.
        $mform->addElement('header', 'skilllevels', get_string('skillslevels', 'tool_skills'));

        // Levels count selection element.
        $options = [0 => get_string('nolevels', 'tool_skills')] + array_combine(range(1, 10), range(1, 10));
        $mform->addElement('select', 'levelscount', get_string('levelscount', 'tool_skills'), $options);
        $mform->setDefault('levelscount', 1);
        $mform->addHelpButton('levelscount', 'levelscount', 'tool_skills');

        $mform->registerNoSubmitButton('updatelevelscount');
        $mform->addElement('submit', 'updatelevelscount', get_string('updatelevelscount', 'tool_skills'), [
            'class' => 'd-none',
        ]);

        $PAGE->requires->js_amd_inline("
            document.querySelector('select[name=levelscount]') !== null ? document.querySelector('select[name=levelscount]')
                .onchange = (e) => document.querySelector('input[name=updatelevelscount]').click() : ''; ");
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

        for ($i = 1; $i <= $levelscount; $i++) {
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

            // Set the default name for this level.
            if (!$mform->getElementValue("levels[$i][name]")) {
                $mform->setDefault("levels[$i][name]", get_string('leveldefaultname', 'tool_skills', $i));
            }

            // Set the default point for this level.
            if ($mform->getElementValue("levels[$i][points]") === null) {
                $mform->setDefault("levels[$i][points]", ($i - 1) * 10);
            }

            // Level color.
            $mform->addElement('tool_skills_colorpicker', "levels[$i][color]", get_string('levelscolor', 'tool_skills', $i), '');
            $mform->setType("levels[$i][color]", PARAM_TEXT);
            $mform->addHelpButton("levels[$i][color]", 'levelscolor', 'tool_skills');

            // Level image.
            $mform->addElement(
                'filemanager',
                "levels[$i][image]",
                get_string('levelsimage', 'tool_skills', $i),
                null,
                self::level_image_options()
            );
            $mform->addHelpButton("levels[$i][image]", 'levelsimage', 'tool_skills');
        }
        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Filemanager/draft-area options for the per-level image. Shared between the form element,
     * the draft area preparation and the file saving in level::manage_level_instance() so they
     * stay consistent.
     *
     * @return array
     */
    public static function level_image_options(): array {
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['web_image'],
        ];
    }

    /**
     * Load in existing data as form defaults. Preprocesses the level image draft areas
     * before passing the data to the parent set_data().
     *
     * @param \stdClass|array $defaultvalues object or array of default values
     */
    public function set_data($defaultvalues) {

        $this->data_preprocessing($defaultvalues); // Include to store the files.

        parent::set_data($defaultvalues);
    }

    /**
     * Process the level image draft areas before the form defaults are set.
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
            'image' => 'levelimage',
        ];

        if (empty($defaultvalues->levels) || !is_array($defaultvalues->levels)) {
            return;
        }

        // Prepare the file manager fields to store images.
        foreach ($filemanagers as $configname => $filearea) {
            // For all levels in this skill (iterate the actual keys to stay index-agnostic).
            foreach ($defaultvalues->levels as $i => $level) {
                if (empty($level)) {
                    continue;
                }
                // Draft item id.
                $draftitemid = file_get_submitted_draft_itemid($filearea);
                // Use the level id as item id.
                $levelid = $level['id'] ?? 0;
                // Store the draft files to area files.
                file_prepare_draft_area(
                    $draftitemid,
                    $context->id,
                    'tool_skills',
                    $filearea,
                    $levelid,
                    self::level_image_options()
                );
                $defaultvalues->levels[$i][$configname] = $draftitemid;
            }
        }
    }

    /**
     * Editor form element options.
     *
     * @param \context $context
     * @return array
     */
    protected function get_editor_options($context = null) {
        global $PAGE;

        return [
            'subdirs' => true,
            'maxfiles' => 1,
            'maxbytes' => 1000000,
            'context' => $context ?: $PAGE->context,
        ];
    }

    /**
     * Validate the user input data. Verified the URL input filled if the item type is static.
     *
     * @param array $data
     * @param array $files
     * @return array List of validation errors, empty array if none.
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

        // Colours are emitted into inline CSS in the profile template, so restrict them to an empty
        // value or a #rgb / #rrggbb hex string to prevent CSS injection through the style attribute.
        $ishexcolour = fn($value) => ($value === '' || $value === null
            || preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string) $value));

        if (isset($data['color']) && !$ishexcolour($data['color'])) {
            $errors['color'] = get_string('error:invalidcolor', 'tool_skills');
        }

        if (!empty($data['levels']) && is_array($data['levels'])) {
            foreach ($data['levels'] as $i => $level) {
                if (isset($level['color']) && !$ishexcolour($level['color'])) {
                    $errors["levels[$i][color]"] = get_string('error:invalidcolor', 'tool_skills');
                }
            }
        }

        return $errors ?? [];
    }
}
