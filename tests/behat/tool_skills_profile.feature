@tool @tool_skills @tool_skills_profile
Feature: Skills section on the user profile
  In order to follow my progress
  As a learner
  I need to see my skills on my profile the way the administrator configured it

  Background:
    Given the following "course" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
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

  @javascript
  Scenario: By default the Skills section lists each skill with its courses
    When I am on the "student1" "user > profile" page logged in as student1
    Then I should see "Skills" in the ".toolskills-profile-category h3" "css_element"
    And I should not see "Skills earned"
    And I should see "Beginner" in the ".toolskills-profile-skills" "css_element"
    And I should see "0" in the ".toolskill-earned" "css_element"
    And I should see "Course 1" in the ".toolskills-profile-skills .toolskill-course" "css_element"

  @javascript
  Scenario: With skill totals only, the Skills section shows no course rows
    Given the following config values are set as admin:
      | profiledisplay | 2 | tool_skills |
    When I am on the "student1" "user > profile" page logged in as student1
    Then I should see "Beginner" in the ".toolskills-profile-skills" "css_element"
    And I should see "0" in the ".toolskill-earned" "css_element"
    And I should not see "Course 1" in the ".toolskills-profile-skills" "css_element"
    And ".toolskills-profile-skills .toolskill-course" "css_element" should not exist
    And ".toolskills-profile-skills .accordion-button" "css_element" should not exist

  @javascript
  Scenario: With the Skills section disabled, the profile has no Skills section
    Given the following config values are set as admin:
      | profiledisplay | 0 | tool_skills |
    When I am on the "student1" "user > profile" page logged in as student1
    Then ".toolskills-profile-category" "css_element" should not exist
    And ".toolskills-profile-skills" "css_element" should not exist
    And I log out
    And I log in as "admin"
    And I follow "Profile" in the user menu
    And ".toolskills-profile-category" "css_element" should not exist

  @javascript
  Scenario: The Skills section setting is on the Skills general settings page
    Given I log in as "admin"
    When I navigate to "Plugins > Admin tools > Skills > General settings" in site administration
    And I set the field "Skills on the user profile" to "Skill totals only"
    And I press "Save changes"
    And I am on the "student1" "user > profile" page logged in as student1
    Then ".toolskills-profile-skills .toolskill-course" "css_element" should not exist
    And I should see "Beginner" in the ".toolskills-profile-skills" "css_element"

  @javascript
  Scenario: Learners see the skills of a course on its Skills page
    When I am on the "Course 1" course page logged in as student1
    And I click on "Skills" "link" in the ".secondary-navigation" "css_element"
    Then I should see "Skills in this course"
    And I should see "Beginner" in the ".toolskills-course-skills" "css_element"
    And I should see "0 points from this course" in the ".toolskills-course-skills" "css_element"
    And I should see "Points - 200" in the ".toolskill-coursecompletion" "css_element"
    And "Manage course skills" "button" should not exist

  @javascript
  Scenario: With the Skills section disabled, learners get no Skills tab in the course
    Given the following config values are set as admin:
      | profiledisplay | 0 | tool_skills |
    When I am on the "Course 1" course page logged in as student1
    Then "Skills" "link" should not exist in the ".secondary-navigation" "css_element"

  @javascript
  Scenario: Teachers follow the participants' progress of a course
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Skills" in current page administration
    And I should see "How it is earned in this course" in the ".toolskills-course-skills" "css_element"
    And I should see "No level: 1" in the ".toolskill-levelcounts" "css_element"
    When I set the field "Course skills navigation" to "Participants' progress"
    Then I should see "Participants' progress" in the ".tertiary-navigation" "css_element"
    And I should see "Student First" in the "tool_skills_course_participants" "table"
    And I should see "From this course" in the "tool_skills_course_participants" "table"
