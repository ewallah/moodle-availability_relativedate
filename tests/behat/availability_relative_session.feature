@eWallah @availability @availability_relativedate @javascript
Feature: availability_relativedate relative sessions
  In order to control student access to activities
  As a teacher
  I need to set session date conditions which prevent student access

  Background:
    Given the following "users" exist:
      | username |
      | student1 |
    And the following config values are set as admin:
      | enableavailability   | 1 |
    And the following "course" exists:
      | fullname          | Course 1             |
      | shortname         | C1                   |
      | category          | 0                    |
      | enablecompletion  | 1                    |
      | startdate         | ## -10 days 17:00 ## |
      | enddate           | ## +2 weeks 17:00 ## |
    And the following "activities" exist:
      | activity | name   | intro | course | idnumber    | section |
      | page     | Page A | intro | C1     | page1       | 1       |
      | page     | Page B | intro | C1     | page2       | 2       |
    And the following "course enrolments" exist:
      | user     | course | role     |
      | student1 | C1     | student  |

  Scenario: Admin should see relative session restrictions
    When I log in as "admin"
    And I am on the "page1" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "0"
    And I set the field "relativestart" to "5"
    And I press "Save and return to course"
    And I should see "Not available unless" in the "region-main" "region"
    And I should see "1 minutes after last visit" in the "region-main" "region"

    And I am on the "page2" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Relative date" "button" in the "Add restriction..." "dialogue"
    And I set the field "relativenumber" to "1"
    And I set the field "relativednw" to "1"
    And I set the field "relativestart" to "5"
    And I press "Save and return to course"
    And I should see "Not available unless" in the "region-main" "region"
    And I should see "1 hours after last visit" in the "region-main" "region"
    And I log out

    # Log in as student 1.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Page A" in the "region-main" "region"
    And I should not see "1 minutes after last visit" in the "region-main" "region"
    And I should see relativedate "## +1 minute ##"
    And I should see relativedate "## +1 hour ##"
    And I log out
    And I trigger cron
    And I am on the "C1" "Course" page logged in as "student1"
    And I should see relativedate "## +1 minute ##"
    And I should see relativedate "## +1 hour ##"
