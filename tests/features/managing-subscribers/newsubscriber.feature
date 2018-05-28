Feature: Import subscribers
  In order to import subscribers 
  As an admin user
  I need to be able to login and import emails
  //this needs to be extended to search for emails added
  
  Scenario: Choose import method 
    Given I have logged in as an administrator
    Given I follow "Subscribers"
    Given I follow "Import subscribers"
    Then I should see "Please choose one of the import methods below"
    And I should see "Copy and paste list of email addresses"
    And I should see "Import by uploading a file with email addresses"
    And I should see "Import by uploading a CSV file with email addresses and additional data"
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
    Then I should see "Select the lists to add the emails to"
    Given I check "importlists[all]"
    And I fill in "importcontent" with "you@domain.com"
    When I press "doimport"
    Then I should see "Send a campaign" 
    And I should see "Import some more emails"


  # Then print last response
  # this fails, even though the address is there
  # And I should see the email address I entered
    #And I follow "History"
    #Then I should see the email address I entered

  Scenario:Import subscribers by uploading a txt file with emails
    Given I have logged in as an administrator
    Given I am on "/lists/admin/?page=import"
    Given I follow "Import by uploading a file with email addresses"
    Then I should see "Select the lists to add the emails to"
    Given I check "importlists[all]"
    When I attach the file "importFile.txt" to "import_file"
    When I press "Import"
    Then I should see "There should only be ONE email per line. If the output looks ok, go Back to resubmit for real." 
    

  Scenario:Import subscribers by uploading a CSV file with emails
    Given I have logged in as an administrator
    Given I am on "/lists/admin/?page=import"
    Given I follow "Import by uploading a CSV file with email addresses and additional data"
    Then I should see "Select the lists to add the emails to"
    Given I check "lists[all]"
    And I attach the file "ImportCSV.csv" to "import_file"
    And I press "Import"
    Then I should see "Reading emails from file"
