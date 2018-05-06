Feature: Login
    In order to access dashboard
    As an admin user
    I need to be able to login

    Scenario: Login as administrator
        Given I am on "/lists/admin/"
        When I fill in "login" with a valid username
        And I fill in "password" with a valid password
        And I press "Continue"
        Then I should see "Start or continue a campaign"

    Scenario: Login with bad credentials
        Given I am on "/lists/admin/"
        When I fill in "login" with "no-user"
        And I fill in "password" with "no-password"
        And I press "Continue"
        Then I should see "Incorrect password"

   Scenario: Login with only a username
         Given I am on "/lists/admin/"
         When I fill in "login" with "no-user"
         And I fill in "password" with ""
         And I press "Continue"
         Then I should see "Please enter your credentials"

     Scenario: Login with only a password
         Given I am on "/lists/admin/"
         When I fill in "login" with ""
         And I fill in "password" with "no-password"
         And I press "Continue"
         Then I should see "Please enter your credentials"
