Feature: Setup
  In order to setup phplist
  As an admin user
  I need to be able to init db

  Scenario: Go to dashboard
    Given I am on "/lists/admin/"
    Then I should see "Database has not been initialised. go to Initialise Database to continue"

  Scenario: Launch the initialisation
    Given I am on "/lists/admin/"
    When I follow "Initialise Database"
    Then I should see "phpList initialisation"

  Scenario: Init database
    Given I am on "/lists/admin/?page=initialise&firstinstall=1"
    When I fill in "adminname" with "admin"
    And I fill in "orgname" with "phplist ltd"
    And I fill in "adminemail" with "admin@phplist.dev"
    And I fill in "adminpassword" with "Mypassword123+"
    And I press "Continue"
    Then I should see "Success:"
