<?php

namespace tool_skills;

use single_button;

class helper {

    /**
     * Generate the button which is displayed on top of the templates table. Helps to create templates.
     *
     * @param bool $filtered Is the table result is filtered.
     * @return string The HTML contents to display the create templates button.
     */
    public static function skills_buttons($tab, $filtered=false) {
        global $OUTPUT, $DB, $CFG;

        require_once($CFG->dirroot. '/admin/tool/skills/locallib.php');

        // Setup create template button on page.
        $caption = get_string('createskill', 'tool_skills');
        $editurl = new \moodle_url('/admin/tool/skills/manage/edit.php', array('sesskey' => sesskey()));

        // IN Moodle 4.2, primary button param depreceted.
        $primary = defined('single_button::BUTTON_PRIMARY') ? single_button::BUTTON_PRIMARY : true;
        $button = new single_button($editurl, $caption, 'get', $primary);
        $button = $OUTPUT->render($button);

        // Filter form.
        $button .= \html_writer::start_div('filter-form-container');
        $button .= \html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('i/filter', 'Filter'), [
            'id' => 'tool-skills-filter',
            'class' => 'sort-toolskills btn btn-primary ml-2 ' . ($filtered ? 'filtered' : '')
        ]);
        $filter = new \tool_skills_table_filter(null, ['t' => $tab]);
        $button .= \html_writer::tag('div', $filter->render(), ['id' => 'tool-skills-filterform', 'class' => 'hide']);
        $button .= \html_writer::end_div();

        // Sort button for the table. Sort y the reference.
       /*  $tdir = optional_param('tdir', null, PARAM_INT);
        $tdir = ($tdir == SORT_ASC) ? SORT_DESC : SORT_ASC;
        $dirimage = ($tdir == SORT_ASC) ? '<i class="fa fa-sort-amount-up"></i>' : $OUTPUT->pix_icon('t/sort_by', 'Sortby');

        $manageurl = new \moodle_url('/admin/tool/skills/manage/list.php', [
            'tsort' => 'reference', 'tdir' => $tdir
        ]);
        $tempcount = $DB->count_records('tool_skills');
        if (!empty($tempcount)) {
            $button .= \html_writer::link($manageurl->out(false), $dirimage.get_string('sort'), [
                'class' => 'sort-toolskills btn btn-primary ml-2'
            ]);
        } */

        return $button;
    }

}
