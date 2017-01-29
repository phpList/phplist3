Feature: Load default attributes
  In order to load default attributes
  As an admin user
  I need to be able to login and load default attributes and add a value

  Scenario: Load default attributes and add a value
    Given I am on "/lists/admin/?page=attributes"
    When I fill in "login" with "admin"
    And I fill in "password" with "Mypassword123+"
    And I press "Continue"
    Then I should see "predefined defaults"
    When I follow "predefined defaults"
    Then I should see "Countries in the world"
    When I fill in the following:
      | selected[]  | be-cities.txt   |
 #   Then print last response
    And I press "add"
    Then I should see "Loading Cities of Belgium"
    And I should see "done"
    When I follow "return to editing attributes"
    Then I should see "Woonplaats"
    And I should see "edit values"
    When I follow "edit values"
 #   Then print last response
    Then I should see "Brussel"
    And I should see "Bruxelles"
    When I follow "Add new"
    Then I should see "Add new Woonplaats, one per line"
    When I fill in the following:
      | itemlist  | Undefined   |
    And I press "Add new Woonplaats"
    Then I should see "Brussel"
    And I should see "Bruxelles"
    And I should see "Undefined"
    And I follow "Back to attributes"
    Then I should see "Existing attributes"
