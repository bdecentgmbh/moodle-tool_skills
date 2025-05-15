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
 * Tool skills - Manage skills, course skills.
 *
 * @module   tool_skills/skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/str', 'core_form/modalform'], function($, ModalFactory, Str, ModalForm) {

    const SELECTORS = {
        table: '#tool_skills_list',
        editskill: '[data-target="toolskill-edit"]',
        skillsRow: '#tool_skills_list .skill-actions a.action-edit'
    };

    class ToolSkillsCourses {

        constructor(skillID, courseID) {

            this.SELECTORS = SELECTORS;
            this.skillCourseID = '';
            this.skillID = skillID;
            this.courseID = courseID;

            this.SELECTORS.root = '#tool_skills_list [data-skillid="' + this.skillID + '"]';
            this.addActionListiners();
        }

        getRoot() {
            return document.querySelector(this.SELECTORS.root);
        }

        showContentForm() {

            var formClass = 'tool_skills\\form\\course_form';

            const modalForm = new ModalForm({

                formClass: formClass,
                // Add as many arguments as you need, they will be passed to the form:
                args: {courseid: this.courseID, skill: this.skillID},
                // Modal configurations, here set modal title.
                modalConfig: {title: Str.get_string('courseskills', 'tool_skills')},
            });

            modalForm.show();

            // Listen to events if you want to execute something on form submit.
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, function() {
                window.location.reload();
            });
        }


        addActionListiners() {

            var self = this;

            this.getRoot().addEventListener('click', function(e) {

                if (e.target.closest(SELECTORS.editskill)) {
                    e.preventDefault();
                    self.showContentForm();
                }
            });
        }

        /**
         * Add event listenrs.
         *
         * @param {Integer} courseID
         */
        static createInstances(courseID) {

            let skills = document.querySelectorAll(SELECTORS.skillsRow);

            const skillsList = [];

            if (skills !== null) {

                var skill;
                skills.forEach((skl) => {
                    var skillID = skl.dataset.skillid;
                    if (skillID in skillsList) {
                        skill = skillsList[skillID];
                    } else {
                        skill = new ToolSkillsCourses(skillID, courseID);
                        skillsList[skillID] = skill;
                    }
                });
            }
        }

        /**
         * Trigger the filter form to submit. to refresh the course content.
         *
         * @param {int} blockID
         */
        static refresh(blockID) {
           // Quick fix. TODO: Need to implement the method in Dashinstance.js to referesh the content from anywhere.
           var block = '#inst' + blockID;
           if ($(block).find('select:eq(1)').length == 0) {
               $(block).find('.filter-form').append('<select style="display:none;"><option>1</option></select>');
           }

           $(block).find('.filter-form').find('select').trigger('change');
       }

    }

    return {

        init: function(courseID) {
            ToolSkillsCourses.createInstances(courseID);
        }
    };
});
