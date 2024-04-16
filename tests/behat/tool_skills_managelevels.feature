@tool @tool_skills @tool_skills_managelevels
Feature: Allocate points to users, need to manage levels and assign skills to courses
  In order to use the features
  As admin
  I need to be able to configure the tool skills plugin

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "course" exist:
      | fullname    | shortname | category | enablecompletion |
      | Course 1    | C1        | 0        |  1         |
      | Course 2    | C2        | 0        |  1         |
      | Course 3    | C3        | 0        |  1         |
    And the following "activities" exist:
      | activity | name     | course | idnumber  |  intro           | section |completion|
      | page     | Test page1 | C1     | page1    | Page description | 1      | 1 |
      | page     | Test page2 | C2     | page1    | Page description | 2      | 1 |
      | page     | Test page3 | C3     | page1    | Page description | 3      | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
      | student2 | Student   | Two   | student2@example.com    |
      | student3 | Student   | Three   | student3@example.com    |
    And the following "course enrolments" exist:
      | user | course | role             |   timestart | timeend   |
      | student1 | C1 | student          |   0         |     0     |
      | student1 | C2 | student          |   0         |     0     |
      | student1 | C3 | student          |   0         |     0     |
      | student2 | C2 | student          |   0         |     0     |
      | student2 | C3 | student          |   0         |     0     |
      | admin    | C1 | manager          |   0         |     0     |
      | admin    | C2 | manager          |   0         |     0     |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Test page1" to "1"
    And I press "Save changes"
    And I create skill with the following fields to these values:
      | Skill name       | Beginner |
      | Key              | beginner |
      | Number of levels | 2        |
      | Base level name    | beginner |
      | Base level point   | 10       |
      | Level #1 name    | Level 1  |
      | Level #1 point   | 20       |
      | Level #2 name    | Level 2  |
      | Level #2 point   | 30       |

  @javascript
  Scenario: Award points to users for course completion
    Given I navigate to "Course 1" course skills
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I set the following fields to these values:
      | Status                 | Enabled  |
      | Upon course completion | Points   |
      | Points                 | 200      |
    And I press "Save changes"
    Then I should see "Points - 200" in the "beginner" "table_row"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I am on the "student1" "user > profile" page
    And I should see "Earned: 0"
    And I am on "Course 1" course homepage
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "student1" "user > profile" page
    Then I should see "Earned: 200"

  @javascript
  Scenario: Set the user points to reach level
    Given I navigate to "Course 1" course skills
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I set the following fields to these values:
      | Status                 | Enabled   |
      | Upon course completion | Set level |
      | Level                  | Level 1   |
    And I press "Save changes"
    Then I should see "Set level - Level 1" in the "beginner" "table_row"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I am on the "student1" "user > profile" page
    And I should see "Earned: 0"
    And I am on "Course 1" course homepage
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "student1" "user > profile" page
    Then I should see "Earned: 20"
    When I log in as "admin"
    And I navigate to "Course 2" course skills
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I set the following fields to these values:
      | Status                 | Enabled   |
      | Upon course completion | Set level |
      | Level                  | Level 1   |
    And I press "Save changes"
    And I am on the "Course 2" course page logged in as student1
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "student1" "user > profile" page
    Then I should see "Earned: 20"

  @javascript
  Scenario: Force the user points to the level
    Given I navigate to "Course 1" course skills
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I set the following fields to these values:
      | Status                 | Enabled   |
      | Upon course completion | Force level |
      | Level                  | Level 2   |
    And I press "Save changes"
    Then I should see "Force level - Level 2" in the "beginner" "table_row"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "student1" "user > profile" page
    Then I should see "Earned: 30"
    When I log in as "admin"
    And I navigate to "Course 2" course skills
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I set the following fields to these values:
      | Status                 | Enabled   |
      | Upon course completion | Force level |
      | Level                  | Level 1   |
    And I press "Save changes"
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Test page2" to "1"
    And I press "Save changes"
    And I am on the "Course 2" course page logged in as student1
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "student1" "user > profile" page
    Then I should see "Earned: 20" in the ".skills-points-C2" "css_element"

  @javascript
  Scenario: Set the negative points to the level
    Given I navigate to "Course 1" course skills
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I set the following fields to these values:
      | Status                 | Enabled   |
      | Upon course completion | Force level |
      | Level                  | Level 2   |
    And I press "Save changes"
    Then I should see "Force level - Level 2" in the "beginner" "table_row"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "student1" "user > profile" page
    Then I should see "Earned: 30"
    When I log in as "admin"
    And I navigate to "Course 2" course skills
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I set the following fields to these values:
      | Status                 | Enabled   |
      | Upon course completion | Points |
      | Points                 | -50    |
    And I press "Save changes"
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Test page2" to "1"
    And I press "Save changes"
    And I am on the "Course 2" course page logged in as student1
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "student1" "user > profile" page
    Then I should see "Earned: -50" in the ".skills-points-C2" "css_element"
    And I should see "Earned: -20" in the ".skill-beginner" "css_element"
