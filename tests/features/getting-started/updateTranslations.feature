@javascript
Feature: Get new translated strings
  In order to get available translated application strings
  As an admin user
  I need to be able to Update Translations

Scenario: Update translations
Given I have logged in as an administrator
And I follow "System"
And I follow "Update translations"
Then I should not see "Error:"
