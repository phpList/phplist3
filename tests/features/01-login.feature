Feature: Login
  In order to access dashboard
  As an admin user
  I need to be able to login

  Scenario: Login as administrator
    Given I am on "/lists/admin/"
    When I fill in "login" with "admin"
    And I fill in "password" with "Mypassword123+"
    And I press "Continue"
    Then I should see "Start or continue a campaign"
