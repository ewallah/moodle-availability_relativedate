@availability @availability_relativedate @javascript
Feature: availability_relativedate
  In order to control student access to activities
  As a teacher
  I need to set date conditions which prevent student access

  Background:
    Given the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    Given the following "courses" exist:
      | fullname | shortname | category | startdate     | endate                     |
      | Course 1 | C1        | 0        | ##yesterday## | ##last day of next month## |
    And the following "activities" exist:
      | activity | course | idnumber  | name            | intro                   | timeopen        | duedate                     |
      | assign   | C1     | assign1   | Test assign 1   | Test assign description | ##tomorrow##    | ##first day of next month## |
    And the following config values are set as admin:
      | enableavailability   | 1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Test condition
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

    And I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | Page 1 |
      | Description  | Test   |
      | Page content | Test   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    Then "input[name=rshort]:not([checked=checked])" "css_element" should exist
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "1"
    And I set the field "relativestart" to "1"
    And I press "Save and return to course"

    And I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | Page 2 |
      | Description  | Test   |
      | Page content | Test   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "2"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "2"
    And I click on ".availability-item .form-check-input" "css_element"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"

    And I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | Page 3 |
      | Description  | Test   |
      | Page content | Test   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "3"
    And I set the field "relativednw" to "3"
    And I set the field "relativestart" to "3"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"

    And I edit the section "2"
    And I expand all fieldsets
    Then I should see "None" in the "Restrict access" "fieldset"
    When I click on "Add restriction..." "button"
    And  I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "5"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "1"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save changes"

    And I edit the section "3"
    And I expand all fieldsets
    Then I should see "None" in the "Restrict access" "fieldset"
    When I click on "Add restriction..." "button"
    And  I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "6"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "2"
    And I press "Save changes"

    Then I should see "Page 1" in the "region-main" "region"
    And I should see "Not available unless: Hour 1" in the "region-main" "region"
    And I should see "Not available unless: This course has no end date (hidden otherwise)" in the "region-main" "region"
    And I should see "Not available unless: Week 3 (hidden otherwise)" in the "region-main" "region"
    And I should see "Not available unless: Day 5 (hidden otherwise)" in the "region-main" "region"
    And I should see "Not available unless: Day 6" in the "region-main" "region"
    
    # Log back in as student.
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage

    # Page 1 should appear, but page 2 does not.
    Then I should see "Page 1" in the "region-main" "region"
    And I should not see "Page 2" in the "region-main" "region"
    And I should not see "Page 3" in the "region-main" "region"
    And I should not see "Section 2" in the "region-main" "region"
    And I should not see "Section 3" in the "region-main" "region"
