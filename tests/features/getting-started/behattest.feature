@behattest
Feature: Setup
  In order to verify that Behat works correctly
  As a behat user
  I need to be able to load the phpList website

  Scenario: Go to phpList 
    Given I am on "https://www.phplist.com"
    Then I must see "Open Source"

