@eWallah @availability @availability_relativedate
Feature: availability_relativedate relative quizes
  In order to use conditions that are based on quizes
  As a teacher
  I need to be able to add a relative date as part of the condition

  Background:
    Given the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "activities" exist:
      | activity | course | name   | idnumber | completion |
      | page     | C1     | Page 1 | pageA    | 1          |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              |
      | Test questions   | truefalse | First question | Answer the first question |
    And the following "activities" exist:
      | activity   | name           | course | idnumber | gradepass | completion | completionpassgrade | completionusegrade |
      | quiz       | Test quiz name | C1     | quiz1    | 5.00      | 2          | 1                   | 1                  |
    And quiz "Test quiz name" contains the following questions:
      | question       | page |
      | First question | 1    |

  @javascript
  Scenario: Relative date restrict access for quiz  completion should display correctly for teachers
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    And I press "Add restriction..."
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the following fields to these values:
      | Required completion status | must be marked complete |
      | cm                         | quiz                    |
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativedmw" to "0"
    And I set the field "relativestart" to "7"
    When I press "Save and return to course"
    And I click on "Show more" "button" in the "Page 1" "core_availability > Activity availability"
    Then I should see "Not available unless:" in the ".activity.page" "css_element"
    And I should see "The activity Test quiz name is marked complete" in the ".activity.page" "css_element"
    And I should see "1 minute after completion of activity Test quiz name" in the ".activity.page" "css_element"

  @javascript
  Scenario Outline: Relative date restrict access for quiz activity completion should display correctly
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    And I press "Add restriction..."
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the following fields to these values:
      | Required completion status | <condition>   |
      | cm                         | quiz          |
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativedmw" to "0"
    And I set the field "relativestart" to "7"
    And I should see "Test quiz name"
    And I press "Save and return to course"

    When I am on the "Course 1" "course" page logged in as "student1"
    And I click on "Show more" "button" in the "Page 1" "core_availability > Activity availability"
    Then I should see "Not available unless:" in the "Page 1" "core_availability > Activity availability"
    And I should see "1 minute after completion of" in the "Page 1" "core_availability > Activity availability"
    # Failed grade for quiz.
    And user "student1" has attempted "Test quiz name" with responses:
      | slot | response |
      | 1    | False    |
    And I reload the page
    And I <shouldornot> see "Not available unless: From" in the "Page 1" "core_availability > Activity availability"

    # Passing grade for quiz.
    But user "student1" has attempted "Test quiz name" with responses:
      | slot | response |
      | 1    | True     |
    And I reload the page
    And "Show more" "button" <showmore> exist in the "Page 1" "core_availability > Activity availability"
    # We have to wait 1 minute, so the locked icon is still there.
    And I <shouldornotanswer> see "Not available unless: From" in the "Page 1" "core_availability > Activity availability"

    Examples:
      | condition                        | showmore   | shouldornot | shouldornotanswer |
      | must be marked complete          | should not | should not  | should            |
      | must be complete with pass grade | should not | should not  | should            |
      | must be complete with fail grade | should     | should      | should not        |
