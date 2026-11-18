@tool @tool_skills @tool_skills_management_links @javascript
Feature: Users who manage skills find the management pages without the profile Skills section
  In order to manage skills when the profile has no Skills section
  As a manager who is not a site administrator
  I need to find Manage skills on the Skills page of a course and in Site administration

  Background:
    Given the following "course" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Mara      | Manager  | manager1@example.com |
      | teacher1 | Terry     | Teacher  | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | manager1 | C1     | manager        |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |
    And the following config values are set as admin:
      | profiledisplay | 0 | tool_skills |
    And I log in as "admin"
    And I create skill with the following fields to these values:
      | Skill name     | Beginner |
      | Key            | beginner |
      | Level #1 name  | beginner |
      | Level #1 point | 10       |
    And I navigate to "Course 1" course skills
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I set the following fields to these values:
      | Status                 | Enabled |
      | Upon course completion | Points  |
      | Points                 | 200     |
    And I press "Save changes"
    And I log out

  Scenario: A manager opens Manage skills from the Skills page of a course
    When I log in as "manager1"
    And I am on "Course 1" course homepage
    And I navigate to "Skills" in current page administration
    And I click on ".tertiary-navigation .dropdown-toggle" "css_element"
    Then I should see "Site-wide" in the ".tertiary-navigation" "css_element"
    And I should see "Manage skills" in the ".tertiary-navigation" "css_element"
    And I should see "Manage course skills" in the ".tertiary-navigation" "css_element"

  Scenario: A teacher gets the course pages, without the pages for managers
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Skills" in current page administration
    And I click on ".tertiary-navigation .dropdown-toggle" "css_element"
    Then I should see "Manage course skills" in the ".tertiary-navigation" "css_element"
    And I should not see "Manage skills" in the ".tertiary-navigation" "css_element"

  Scenario: A manager opens Manage skills from Site administration
    When I log in as "manager1"
    # The Skills section is under Plugins > Admin tools on Moodle 5.x and of its own on Moodle 4.5.
    And I visit "/admin/category.php?category=skills"
    And I follow "Manage skills"
    Then I should see "Skills" in the "page-header" "region"
    And I should see "Create skill"
