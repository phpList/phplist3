Feature: Create new campaign
  In order to create a new campaign
  As an admin user
  I need to be able to login and start a new campaign

  Scenario: Login and create a campaign
    Given I am on "/lists/admin/?page=attributes"
    When I fill in "login" with "admin"
    And I fill in "password" with "Mypassword123+"
    And I press "Continue"
    Then I should see "predefined defaults"
    When I follow "predefined defaults"
    Then I should see "Countries in the world"
    When I fill in the following:
      | selected[]  | gendersp.txt   |
 #   Then print last response
    And I press "add"
    Then I should see "Loading Sexo"
    And I should see "done"
    When I follow "return to editing attributes"
    Then I should see "Sexo"
    And I should see "edit values"
    When I follow "edit values"
 #   Then print last response
    Then I should see "Femenino"
    And I should see "Masculino"
    When I fill in the following:
      | listorder[1]  | 50   |
      | listorder[2]  | 10   |
    And I press "Change order"
    Then I should see "Femenino"
    And I should see "Masculino"
    When I follow "Add new"
    Then I should see "Add new Sexo, one per line"
    When I fill in the following:
      | itemlist  | Undefined   |
    And I press "Add new Sexo"
    Then I should see "Femenino"
    And I should see "Masculino"
    And I should see "Undefined"
    And I follow "Back to attributes"
    Then I should see "Existing attributes"
