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

            this.SELECTORS.root = '#tool_skills_list [data-skillid="'+this.skillID+'"]';
            this.addActionListiners();

        }

        getRoot() {
            console.log(this.SELECTORS.root);
            return document.querySelector(this.SELECTORS.root);
        }

        showContentForm() {

            var formClass = 'tool_skills\\form\\course_form';

            const modalForm = new ModalForm({

                formClass: formClass,
                // Add as many arguments as you need, they will be passed to the form:
                args: {courseid: this.courseID,  skill: this.skillID},
                // Modal configurations, here set modal title.
                modalConfig: {title: Str.get_string('courseskills', 'tool_skills')},
                // DOM element that should get the focus after the modal dialogue is closed:
                // returnFocus: element,
            });

            modalForm.show();

            // this.dynamicForm.load({instanceid: this.instanceID, widgetname: widgetName, blockid: this.blockID});

            // Listen to events if you want to execute something on form submit. Event detail will contain everything the process() function returned:
            // if (addListener) {
            var self = this;
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, function(e) {
                var form = e.target.querySelector('form');
                // var blockID = (new FormData(form)).get('skill');
                // ToolSkillsCourses.refresh(blockID);
                window.location.reload();
            })
        };


        addActionListiners() {

            var self = this;

            this.getRoot().addEventListener('click', function(e) {

                if (e.target.closest(SELECTORS.editskill)) {
                    e.preventDefault();
                    // self.layoutID = e.target.closest(SELECTORS.addblock).dataset.layoutid;
                    self.showContentForm();
                }
            }.bind(this));
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

                skills.forEach((skl) => {
                    var skillID = skl.dataset.skillid;
                    if (skillID in skillsList) {
                        var skill = skillsList[skillID];
                    } else {
                        var skill = new ToolSkillsCourses(skillID, courseID);
                        skillsList[skillID] = skill;
                    }
                });
            }
        };

        /**
         * Trigger the filter form to submit. to refresh the course content.
         */
        static refresh(blockID) {
           // Quick fix. TODO: Need to implement the method in Dashinstance.js to referesh the content from anywhere.
           var block = '#inst'+blockID;
           if ($(block).find('select:eq(1)').length == 0 ) {
               $(block).find('.filter-form').append('<select style="display:none;"><option>1</option></select>');
           }
           console.log($(block).find('.filter-form').find('select'));

           $(block).find('.filter-form').find('select').trigger('change');
       }

    }

    return {

        init: function(courseID) {
            ToolSkillsCourses.createInstances(courseID);
        }
    }
})
