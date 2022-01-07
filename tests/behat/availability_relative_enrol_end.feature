@eWallah @availability @availability_relativedate
Feature: availability relative enrol end date
  In order to control student access to activities
  As a teacher
  I need to set date conditions which prevent student access
  Based on enrol end date

  Background:
    Given the following "courses" exist:
      | fullname  | shortname | category | format | startdate         | enddate           | enablecompletion |
      | Course 1  | C1        | 0        | topics | ##-10 days noon## | ##+10 days noon## | 1                |
      | Course 2  | C2        | 0        | topics | ##-10 days noon## | ##+20 days noon## | 1                |
    And selfenrolment exists in course "C1" ending "##tomorrow 17:00##"
    And selfenrolment exists in course "C2" ending "##+10 days noon ##"
    And the following "activities" exist:
      | activity | name   | intro | course | idnumber    | section |
      | page     | Page A | intro | C1     | page1       | 1       |
      | page     | Page B | intro | C1     | page2       | 2       |
      | page     | Page A | intro | C2     | pageA       | 1       |
      | page     | Page B | intro | C2     | pageB       | 2       |
    And the following "users" exist:
      | username | timezone        |
      | teacher1 | Australia/Perth |
      | student1 | Australia/Perth |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | student1 | C2     | student        |

  @javascript
  Scenario: Test enrol end date condition
    When I log in as "teacher1"
    And I am on the "page1" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "4"
    And I press "Save and return to course"
    And I should see "Not available unless" in the "region-main" "region"

    When I am on the "page2" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "4"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"
    Then I should see "1 days after enrolment method end date" in the "region-main" "region"

    When I am on the "pageA" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "4"
    And I press "Save and return to course"
    And I should see "Not available unless" in the "region-main" "region"

    When I am on the "pageB" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "2"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "4"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"

    Then I should see "1 days after enrolment method end date" in the "region-main" "region"
    And I should see "2 days after enrolment method end date" in the "region-main" "region"
    And I log out

    # Log in as student 1.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Page A" in the "region-main" "region"
    And I should see "Not available unless" in the "region-main" "region"
    And I should see "1 days after enrolment method end date" in the "region-main" "region"
    And I should not see "Page B" in the "region-main" "region"
    And I should not see "2 days after enrolment method end date" in the "region-main" "region"

    When I am on the "C2" "Course" page
    Then I should see "Page A" in the "region-main" "region"
    And I should see "Not available unless" in the "region-main" "region"
    And I should see "1 days after enrolment method end date" in the "region-main" "region"
    And I should not see "Page B" in the "region-main" "region"
    And I should not see "2 days after enrolment method end date" in the "region-main" "region"
