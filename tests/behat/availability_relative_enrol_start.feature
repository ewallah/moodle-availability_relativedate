@eWallah @availability @availability_relativedate
Feature: availability relative enrol start date
  In order to control student access to activities
  As a teacher
  I need to set date conditions which prevent student access
  Based on enrol start date

  Background:
    Given the following "courses" exist:
      | fullname  | shortname | category | format | startdate          | enablecompletion |
      | Course 1  | C1        | 0        | topics | ##-10 days noon ## | 1                |
      | Course 2  | C2        | 0        | topics | ##-10 days noon ## | 1                |
    And selfenrolment exists in course "C1" starting "##-5 days 17:00##"
    And selfenrolment exists in course "C2" starting "##+5 days 17:00##"
    And the following "activities" exist:
      | activity   | name   | intro | course | idnumber    | section | visible |
      | page       | Page A | intro | C1     | pageA       | 1       | 1       |
      | page       | Page B | intro | C1     | pageB       | 2       | 1       |
      | page       | Page C | intro | C2     | pageC       | 1       | 1       |
      | page       | Page D | intro | C2     | pageD       | 2       | 1       |
    And the following "users" exist:
      | username | timezone |
      | teacher1 | 5        |
      | student1 | 5        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | student1 | C2     | student        |

  @javascript
  Scenario: Test enrol start date condition
    When I am on the "pageA" "page activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "3"
    And I press "Save and return to course"
    And I should see "Not available unless" in the "region-main" "region"

    When I am on the "pageB" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "2"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "3"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"

    Then I should see "1 days after user enrolment date" in the "region-main" "region"
    And I should see "2 days after user enrolment date" in the "region-main" "region"

    When I am on the "pageC" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "3"
    And I press "Save and return to course"
    And I should see "Not available unless" in the "region-main" "region"

    When I am on the "pageD" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "2"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "3"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"

    Then I should see "1 days after user enrolment date" in the "region-main" "region"
    And I should see "2 days after user enrolment date" in the "region-main" "region"
    And I log out

    # Log in as student 1.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Page A" in the "region-main" "region"
    And I should see "Not available unless" in the "region-main" "region"
    And I should not see "1 days after user enrolment date" in the "region-main" "region"
    And I should not see "Page B" in the "region-main" "region"
    And I should not see "2 days after user enrolment date" in the "region-main" "region"

    When I am on the "C2" "Course" page
    Then I should see "Page C" in the "region-main" "region"
    And I should see "Not available unless" in the "region-main" "region"
    And I should not see "1 days after user enrolment date" in the "region-main" "region"
    And I should not see "Page D" in the "region-main" "region"
    And I should not see "2 days after user enrolment date" in the "region-main" "region"
