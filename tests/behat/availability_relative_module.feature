@eWallah @availability @availability_relativedate @javascript
Feature: availability_relativedate relative activities
  In order to control student access to activities
  As a teacher
  I need to set activitie date conditions which prevent student access

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
    And the following "course enrolments" exist:
      | user     | course | role     |
      | student1 | C1     | student  |
    And the following "activities" exist:
      | activity   | name    | course | idnumber | section | completion |
      | page       | Page A1 | C1     | pageA1   | 1       | 1          |
      | page       | Page A2 | C1     | pageA2   | 1       | 1          |
      | page       | Page A3 | C1     | pageA3   | 1       | 1          |
      | page       | Page A4 | C1     | pageA4   | 1       | 1          |
      | page       | Page A5 | C1     | pageA5   | 1       | 1          |
      | page       | Page B1 | C1     | pageB1   | 2       | 1          |
      | page       | Page B2 | C1     | pageB2   | 2       | 1          |
      | page       | Page B3 | C1     | pageB3   | 2       | 1          |
      | page       | Page B4 | C1     | pageB4   | 2       | 1          |
      | page       | Page B5 | C1     | pageB5   | 2       | 1          |
      | page       | Page C1 | C1     | pageC1   | 3       | 1          |
      | page       | Page C2 | C1     | pageC2   | 3       | 1          |
      | page       | Page C3 | C1     | pageC3   | 3       | 1          |
      | page       | Page C4 | C1     | pageC4   | 3       | 1          |
      | page       | Page C5 | C1     | pageC5   | 3       | 1          |
      | page       | Page D1 | C1     | pageD1   | 4       | 1          |
      | page       | Page D2 | C1     | pageD2   | 4       | 1          |
      | page       | Page D3 | C1     | pageD3   | 4       | 1          |
      | page       | Page D4 | C1     | pageD4   | 4       | 1          |
      | page       | Page D5 | C1     | pageD5   | 4       | 1          |
      | page       | Page E1 | C1     | pageE1   | 5       | 1          |
      | page       | Page E2 | C1     | pageE2   | 5       | 1          |
      | page       | Page E3 | C1     | pageE3   | 5       | 1          |
      | page       | Page E4 | C1     | pageE4   | 5       | 1          |
      | page       | Page E5 | C1     | pageE5   | 5       | 1          |
    And I make "pageA2" relative date depending on "pageA1"
    And I make "pageA3" relative date depending on "pageA2"
    And I make "pageA4" relative date depending on "pageA3"
    And I make "pageA5" relative date depending on "pageA4"
    And I make "pageB2" relative date depending on "pageB1"
    And I make "pageB3" relative date depending on "pageB2"
    And I make "pageB4" relative date depending on "pageB3"
    And I make "pageB5" relative date depending on "pageB4"
    And I make "pageC2" relative date depending on "pageC1"
    And I make "pageC3" relative date depending on "pageC2"
    And I make "pageC4" relative date depending on "pageC3"
    And I make "pageC5" relative date depending on "pageC4"
    And I make "pageD2" relative date depending on "pageD1"
    And I make "pageD3" relative date depending on "pageD2"
    And I make "pageD4" relative date depending on "pageD3"
    And I make "pageD5" relative date depending on "pageD4"
    And I make "pageE2" relative date depending on "pageE1"
    And I make "pageE3" relative date depending on "pageE2"
    And I make "pageE4" relative date depending on "pageE3"
    And I make "pageE5" relative date depending on "pageE4"
    And I log in as "admin"
    And I am on site homepage
    And I follow "Purge all caches"
    Then I should see "All caches were purged"

  Scenario: Admin should see relative session restrictions
    When I am on "Course 1" course homepage
    Then I should see "Not available unless: (1 hours after completion of activity"

  Scenario: Student should see relative session restrictions
    When I log out
    And I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Page A1" in the "region-main" "region"
    And I press "Mark as done"
    And I log out
    And I trigger cron
    And I am on the "C1" "Course" page logged in as "student1"
    And I should see "1 hours after completion of activity" in the "region-main" "region"
    Then I should see relativedate "## +1 hour ##"
