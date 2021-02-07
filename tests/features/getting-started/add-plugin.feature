@wip
Feature: Update plugin
    In order to user extra functions of phpPlist
    As an admin user
    I need to be able to add, update or remove plugins

Scenario: Install new Plugin and update
        Given I have logged in as an administrator
        And I am on "/lists/admin/?page=plugins"
        When I fill in "pluginurl" with "https://github.com/bramley/phplist-plugin-autoresponder/archive/master.zip"
        And I press "download"
        Then I should see "Plugin installed successfully"
        Given I follow "Continue" 
        And I press "update" 
        Then I wait for 5 seconds
        When I confirm the pop up 
        Then I should not see "failed"
