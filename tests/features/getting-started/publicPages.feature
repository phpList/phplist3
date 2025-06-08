Feature: Use public pages as a subscriber
  In order to interact with a phplist installation
  As a subscriber
  I need to be able to view and use public pages

Scenario: Use public pages links
    Given I am on "/lists"
    Then I must see "Subscribe to our Newsletter"
    And I must see "Update your preferences"
    And I must see "Unsubscribe from our Newsletters"
    And I must see "Contact the administrator"
    Given I follow "Subscribe to our Newsletter"
    Then I must see "Email address *"
    Given I go back
    And I follow "Update your preferences"
    Then I must see "This page requires a personal identification that can be found on each message that you receive."
    Given I go back 
    And I follow "Unsubscribe from our Newsletters"
    Then I must see "Please enter a valid email address:"
 
