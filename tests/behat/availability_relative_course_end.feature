@eWallah @availability @availability_relativedate
Feature: availability relative course enddate
  In order to control student access to activities
  As a teacher
  I need to set date conditions which prevent student access
  Based on course end date

  Background:
    Given the following "courses" exist:
      | fullname  | shortname | category | format | startdate    | enddate      | enablecompletion |
      | Course 1  | C1        | 0        | topics | ##-20 days## | ##-10 days## | 1                |
      | Course 2  | C2        | 0        | topics | ##-20 days## | ##+10 days## | 1                |
    And the following "activities" exist:
      | activity   | name   | intro | course | idnumber    | section | visible |
      | page       | Page A | intro | C1     | page1       | 1       | 1       |
      | page       | Page B | intro | C1     | page2       | 2       | 1       |
      | page       | Page A | intro | C2     | page1       | 1       | 1       |
      | page       | Page B | intro | C2     | page2       | 2       | 1       |
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
  Scenario: Test end date condition
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "enddate[hour]" to "10"
    And I set the field "enddate[minute]" to "0"
    And I press "Save and display"

    When I follow "Page A"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "2"
    And I press "Save and return to course"
    And I should see "Not available unless" in the "region-main" "region"

    When I follow "Page B"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "2"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "2"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"

    Then I should see "1 days before course end date" in the "region-main" "region"
    And I should see "2 days before course end date" in the "region-main" "region"

    When I am on "Course 2" course homepage with editing mode on
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "enddate[hour]" to "5"
    And I set the field "enddate[minute]" to "0"
    And I press "Save and display"

    When I follow "Page A"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "2"
    And I press "Save and return to course"
    And I should see "Not available unless" in the "region-main" "region"

    When I follow "Page B"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "2"
    And I set the field "relativednw" to "2"
    And I set the field "relativestart" to "2"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"

    Then I should see "1 days before course end date" in the "region-main" "region"
    And I should see "2 days before course end date" in the "region-main" "region"
    And I log out

    # Log in as student 1.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Page A" in the "region-main" "region"
    And I should not see "1 days before course end date" in the "region-main" "region"
    And I should see "Page B" in the "region-main" "region"
    And I should not see "2 days before course end date" in the "region-main" "region"
    And I should not see "Not available unless" in the "region-main" "region"

    When I am on the "C2" "Course" page
    Then I should see "Page A" in the "region-main" "region"
    And I should see "Not available unless" in the "region-main" "region"
    And I should not see "1 days before course end date" in the "region-main" "region"
    And I should not see "Page B" in the "region-main" "region"
    And I should not see "2 days before course end date" in the "region-main" "region"
