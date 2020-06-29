@eWallah @availability @availability_relativedate @javascript
Feature: availability_relativedate
  In order to control student access to activities
  As a teacher
  I need to set date conditions which prevent student access

  Background:
    Given the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "courses" exist:
      | fullname | shortname | category | startdate     | enddate                    |
      | Course 1 | C1        | 0        | ##yesterday## | ##last day of next month## |
    And the following config values are set as admin:
      | enableavailability   | 1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: Restrict section0
    When I edit the section "0"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Relative date" "button" should not exist in the "Add restriction..." "dialogue"

  Scenario: Test condition
    When I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | Page 1 |
      | Description  | Test   |
      | Page content | Test   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
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

    And I edit the section "2"
    And I expand all fieldsets
    Then I should see "5" in the "Restrict access" "fieldset"
    And I should see "days" in the "Restrict access" "fieldset"
    And I should see "after course start date" in the "Restrict access" "fieldset"
    And I press "Cancel"

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
    And I should see "From 1 hour after course start date" in the "region-main" "region"
    And I should see "Until 2 days before course end date" in the "region-main" "region"
    And I should see "From 3 weeks after user enrolment date" in the "region-main" "region"
    And I should see "From 5 days after course start dat" in the "region-main" "region"
    And I should see "Until 6 days before course end date" in the "region-main" "region"
    And I log out

    # Log back in as student.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Page 1" in the "region-main" "region"
    And I should not see "Page 2" in the "region-main" "region"
    And I should not see "Page 3" in the "region-main" "region"
    And I should not see "Section 2" in the "region-main" "region"
    And I should not see "Section 3" in the "region-main" "region"
