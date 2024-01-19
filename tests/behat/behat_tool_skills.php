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
 * Theme Skills - Custom Behat rules for skills
 *
 * @package    tool_skills
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\{TableNode, PyStringNode};

/**
 * Class behat_tool_skills
 *
 * @package    tool_skills
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_skills extends behat_base {


    /**
     * Open the skills listing page.
     *
     * @Given /^I navigate to skills$/
     *
     */
    public function i_navigate_to_skills() {
        $this->execute('behat_navigation::i_navigate_to_in_site_administration',
            ["Plugins > Admin tools > Skills"]);
    }

    /**
     * Open the course skill listing page for the course.
     *
     * @Given /^I navigate to "(?P<coursename>(?:[^"]|\\")*)" course skills$/
     * @param string $coursename Coursename.
     */
    public function i_navigate_to_course_skills($coursename) {
        $this->execute('behat_navigation::i_am_on_course_homepage', [$coursename]);
        $this->execute('behat_general::i_click_on_in_the', ["More", "link", '.secondary-navigation', "css_element"]);
        $this->execute('behat_general::i_click_on', ["Manage skills", "link"]);
    }

    /**
     * Fills a skills to create form with field/value data.
     *
     * @Given /^I create skill with the following fields to these values:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param TableNode $data
     */
    public function i_create_skill_with_the_following_fields_to_these_values(TableNode $data) {

        $this->execute('behat_navigation::i_navigate_to_in_site_administration',
            ["Plugins > Admin tools > Skills"]);
        $this->execute("behat_general::i_click_on", ["Create skill", "button"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$data]);
        $this->execute("behat_general::i_click_on", ["Save changes", "button"]);
    }

    /**
     * This can be used on confirmation message css element.
     *
     * @Given /^I navigate to confirmation$/
     *
     * @throws ExpectationException
     * @return void
     */
    public function i_navigate_to_confirmation() {
        global $CFG;

        $cssclass = ($CFG->branch <= "402") ? '.confirmation-dialogue' : '.modal-footer';
        $this->execute("behat_general::i_click_on", ["Yes", "button", $cssclass, "css_element"]);
    }

    /**
     * Confirmation messages text.
     *
     * @Given /^I should see "(?P<messagetext>(?:[^"]|\\")*)" message confirmation$/
     * @param string $messagetext Messagetext.
     */
    public function i_should_see_message_confirmation($messagetext) {
        global $CFG;

        $cssclass = ($CFG->branch <= "402") ? '.confirmation-dialogue' : '.modal-body';
        $this->execute("behat_general::assert_element_contains_text", [
            "Are you sure! do you want to $messagetext this skill and its levels",
            $cssclass, "css_element",
        ]);
    }

}
