@eWallah @availability @availability_relativedate @javascript
Feature: availability_relativedate
  In order to control student access to activities
  As a teacher
  I need to be able to restrict fora

  Background:
    Given the following "users" exist:
      | username | timezone |
      | teacher1 | 5        |
      | student1 | 5        |
    And the following config values are set as admin:
      | enableavailability   | 1 |
    And the following "course" exists:
      | fullname          | Course 1             |
      | shortname         | C1                   |
      | category          | 0                    |
      | enablecompletion  | 1                    |
      | numsections       | 5                    |
    And the following "activities" exist:
      | activity | course | idnumber | name   | type    | completion |
      | forum    | C1     | forumA   | ForumA | general | 1          |
      | forum    | C1     | forumB   | ForumB | general | 1          |
      | forum    | C1     | forumC   | ForumC | general | 1          |
    And the following "course enrolments" exist:
      | user     | course | role           | timestart             |
      | teacher1 | C1     | editingteacher | ## yesterday 17:00 ## |
      | student1 | C1     | student        | ## yesterday 17:00 ## |

  Scenario: Restrict fora
    Given I am on the "forumA" "forum activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Date" "button" in the "Add restriction..." "dialogue"
    And I set the field "year" to "2026"
    And I press "Save and return to course"

    And I am on the "forumB" "forum activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativedmw" to "2"
    And I set the field "relativestart" to "7"
    And I set the field "relativecoursemodule" to "ForumA"
    And I press "Save and return to course"
    And I should see "1 day after completion of"

    And I am on the "forumC" "forum activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativedmw" to "2"
    And I set the field "relativestart" to "7"
    And I set the field "relativecoursemodule" to "ForumB"
    And I click on "Add restriction..." "button"
    And I click on "Date" "button" in the "Add restriction..." "dialogue"
    And I set the field "year" to "2026"

    And I press "Save and return to course"
    And I should see "1 day after completion of"
    And I log out

    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "ForumA" in the "region-main" "region"
    And I should see "ForumB" in the "region-main" "region"
    And I should see "ForumC" in the "region-main" "region"
    And I should see "1 day after completion of"
