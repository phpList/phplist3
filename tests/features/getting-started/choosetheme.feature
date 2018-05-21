Feature: Choose Theme
    In order to start using the dressprow theme
    As an admin user
    I need to be able to change the default configuration settings

 Scenario: Change theme from trevelin to dressprow
         Given I have logged in as an administrator
         Given I follow "Config"
         And I follow "Settings"
         Then I should be on "lists/admin/?page=configure"
         Given I am on "/lists/admin/?page=configure&id=UITheme"
         Then I should see "Trevelin"
         Given I select "Dressprow" from "values[UITheme]"
         And I press "Save changes"
         #Element not clickable at point
         #Given I follow "logout"
         Given I am on "/lists/admin/?page=logout"
         And I fill in "login" with a valid username
         And I fill in "password" with a valid password
         And I press "Continue"
         Then The header color should be black
        