
@wip
Feature: Load default attributes
  In order to load default attributes
  As an admin user
  I need to be able to login and load default attributes and add a value

  Scenario: Load default attributes and add a value
    Given I have logged in as an administrator
    And I am on "/lists/admin/?page=attributes"
    Then I must see "predefined defaults"
    When I follow "Predefined defaults"
    Then I must see "Countries in the world"
    When I fill in the following:
      | selected[]  | be-cities.txt   |
 #   Then print last response
    And I press "Add"
    Then I must see "Loading Cities of Belgium"
    And I must see "done"
    When I follow "Return to editing attributes"
    Then I must see "Woonplaats"
    And I must see "Edit values"
    When I follow "Edit values"
 #   Then print last response
    Then I must see "Brussel"
    And I must see "Bruxelles"
    When I follow "Add new"
    Then I must see "Add new Woonplaats, one per line"
    When I fill in the following:
      | itemlist  | Undefined   |
    And I press "Add new Woonplaats"
    Then I must see "Brussel"
    And I must see "Bruxelles"
    And I must see "Undefined"
    And I follow "Back to attributes"
    Then I must see "Existing attributes"
