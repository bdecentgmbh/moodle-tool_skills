@tool @tool_skills @tool_skills_generalsettings
Feature: Configuring the tool_skills plugin on the "Skills" page, applying different configurations to individual skills
  In order to use the features
  As admin
  I need to be able to configure the tool skills plugin

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "course" exist:
      | fullname    | shortname | category |
      | Course 1    | C1        | 0        |
    Given I log in as "admin"

  @javascript
  Scenario: Create a basic skill
    When I log in as "admin"
    And I navigate to skills
    And I should see "Active skills" in the "#region-main .nav-tabs .nav-item" "css_element"
    And I should see "Skills are not created yet or not in use"
    And I click on "Create skill" "button"
    And I set the following fields to these values:
      | Skill name      | Begineer |
      | Key             | begineer |
      | Level #0 name   | begineer |
      | Level #0 point  | 10       |
    And I click on "Save changes" "button"
    And I should see "Begineer" in the "tool_skills_list" "table"

  @javascript
  Scenario: Change the skills visibility
    When I log in as "admin"
    And I create skill with the following fields to these values:
      | Skill name      | Begineer |
      | Key             | begineer |
      | Level #0 name   | begineer |
      | Level #0 point  | 10       |
    And ".skill-item-actions .custom-control-input:checked" "css_element" should exist in the "begineer" "table_row"
    Then I am on "Course 1" course homepage
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Manage skills" "link"
    Then I should see "Begineer"
    And I navigate to skills
    And I click on ".skill-item-actions .toolskills-status-switch" "css_element" in the "begineer" "table_row"
    And ".skill-item-actions .toolskills-status-switch.action-show" "css_element" should exist in the "begineer" "table_row"
    Then I am on "Course 1" course homepage
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Manage skills" "link"
    Then I should not see "Begineer"

  @javascript
  Scenario: Update the existing skills and levels
    When I log in as "admin"
    And I create skill with the following fields to these values:
      | Skill name      | Begineer |
      | Key             | begineer |
      | Level #0 name   | begineer |
      | Level #0 point  | 10       |
    Then I should see "Begineer"
    And ".skill-item-actions .action-edit" "css_element" should exist in the "begineer" "table_row"
    And I click on ".skill-item-actions .action-edit" "css_element" in the "begineer" "table_row"
    And I set the following fields to these values:
      | Skill name  | Critical thinker  |
    And I click on "Save changes" "button"
    Then I should not see "Begineer" in the "begineer" "table_row"
    And I should see "Critical thinker" in the "begineer" "table_row"

  @javascript
  Scenario: Archive and unarchive the skills
    When I log in as "admin"
    And I navigate to skills
    And I click on "Create skill" "button"
    And I set the following fields to these values:
      | Skill name      | Begineer |
      | Key             | begineer |
      | Level #0 name   | begineer |
      | Level #0 point  | 10       |
    And I click on "Save changes" "button"
    And I should see "Begineer" in the "tool_skills_list" "table"
    And I click on ".skill-item-actions .action-archive" "css_element" in the "begineer" "table_row"
    And I should see "archive" message confirmation
    And I navigate to confirmation
    And I should see "Begineer" in the "tool_skills_archived_list" "table"
    And I click on "Active skills" "link"
    Then I should see "Skills are not created yet or not in use"
    And I click on "Archived skills" "link"
    And I click on ".skill-item-actions .action-active" "css_element" in the "begineer" "table_row"
    And I should see "activate" message confirmation
    And I navigate to confirmation
    And I should see "Begineer" in the "tool_skills_list" "table"

  @javascript
  Scenario: Delete skills and its levels
    When I log in as "admin"
    And I create skill with the following fields to these values:
      | Skill name      | Begineer |
      | Key             | begineer |
      | Level #0 name   | begineer |
      | Level #0 point  | 10       |
    And I should see "Begineer" in the "tool_skills_list" "table"
    And I click on ".skill-item-actions .action-archive" "css_element" in the "begineer" "table_row"
    And I navigate to confirmation
    And I click on "Archived skills" "link"
    And I click on ".skill-item-actions .action-delete" "css_element" in the "begineer" "table_row"
    And I should see "delete" message confirmation
    And I navigate to confirmation
    Then I should not see "Begineer" in the "#region-main" "css_element"
    And I create skill with the following fields to these values:
      | Skill name      | Critical thinker |
      | Key             | critical-thinker |
      | Level #0 name   | begineer |
      | Level #0 point  | 20       |
    Then I am on "Course 1" course homepage
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Manage skills" "link"
    Then I should see "Critical thinker"
    And I navigate to skills
    And I click on ".skill-item-actions .action-archive" "css_element" in the "critical-thinker" "table_row"
    And I navigate to confirmation
    And I click on "Archived skills" "link"
    And I click on ".skill-item-actions .action-delete" "css_element" in the "critical-thinker" "table_row"
    And I should see "delete" message confirmation
    And I navigate to confirmation
    Then I am on "Course 1" course homepage
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Manage skills" "link"
    And I should not see "Critical thinker"

  @javascript
  Scenario: Create multiple levels
    When I log in as "admin"
    And I create skill with the following fields to these values:
      | Skill name      | Begineer |
      | Key             | begineer |
      | Level #0 name   | begineer |
      | Level #0 point  | 10       |
    Then I should see "Begineer"
    And ".skill-item-actions .action-edit" "css_element" should exist in the "begineer" "table_row"
    And I click on ".skill-item-actions .action-edit" "css_element"
    And I set the field "Number of levels" to "3"
    And I set the following fields to these values:
      | Level #1 name | Level 1 |
      | Level #2 name | Level 2 |
      | Level #3 name | Level 3 |
    And I press "Save changes"
    Then I am on "Course 1" course homepage
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Manage skills" "link"
    And I should see "Begineer"
    And I click on ".skill-course-actions .action-edit" "css_element"
    And I should see "Set course skills" in the ".modal-header" "css_element"
    And I set the field "Status" to "Enabled"
    And I set the field "Upon course completion" to "Set level"
    Then I should see "Level" in the ".modal-body form" "css_element"
    And I click on ".custom-select" "css_element"
    Then I should see "Level 1"
    Then I should see "Level 2"
    Then I should see "Level 3"
