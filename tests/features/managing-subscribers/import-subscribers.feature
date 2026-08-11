@wip
Feature: Import subscribers
  In order to import subscribers 
  As an admin user
  I need to be able to login and import email addresses
  //this needs to be extended to search for emails added
  
  Scenario: Choose import method 
    Given I have logged in as an administrator
    Given I follow "Subscribers"
    Given I follow "Import subscribers"
    Then I must see "Please choose one of the import methods below"
    And I must see "Copy and paste list of email addresses"
    And I must see "Import by uploading a file with email addresses"
    And I must see "Import by uploading a CSV file with email addresses and additional data"
    When I follow "Copy and paste list of email addresses"
    Then I should be on "/lists/admin/?page=importsimple"
    Given I go back
    When I follow "Import by uploading a file with email addresses"
    Then I should be on "/lists/admin/?page=import1"
    Given I go back
    And I follow "Import by uploading a CSV file with email addresses and additional data"
    Then I should be on "/lists/admin/?page=import2"

  Scenario:Import subscribers using copy&paste 
    Given I have logged in as an administrator
    Given I am on "/lists/admin/?page=import"
    Given I follow "Copy and paste list of email addresses"
    Then I must see "Select the lists to add the emails to"
    Given I check "importlists[2]"
    And I fill in "importcontent" with "you@domain.com"
    When I press "doimport"
    Then I must see "Send a campaign" 
    And I must see "Import some more emails"

  Scenario:Import subscribers by uploading a txt file with emails
    Given I have logged in as an administrator
    Given I am on "/lists/admin/?page=import"
    Given I follow "Import by uploading a file with email addresses"
    Then I must see "Select the lists to add the emails to"
    Given I check "importlists[2]"
    When I attach the file "importFile.txt" to "import_file"
    When I press "Import"
    Then I must see "There should only be ONE email per line. If the output looks ok, go Back to resubmit for real." 
    

  Scenario:Import subscribers by uploading a CSV file with emails
    Given I have logged in as an administrator
    Given I am on "/lists/admin/?page=import"
    Given I follow "Import by uploading a CSV file with email addresses and additional data"
    Then I must see "Select the lists to add the emails to"
    Given I check "lists[2]"
    And I attach the file "ImportCSV.csv" to "import_file"
    And I press "Import"
    Then I must see "Reading emails from file"
