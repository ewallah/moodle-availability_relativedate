@eWallah @availability @availability_relativedate
Feature: Restriction by relative date installation succeeds
  In order to use this plugin
  As a user
  I need the installation to work

  Scenario: Check the Plugins overview for the name of this plugin
    Given I log in as "admin"
    And I navigate to "Plugins > Plugins overview" in site administration
    Then the following should exist in the "plugins-control-panel" table:
      |Restriction by relative date|
      |availability_relativedate|
