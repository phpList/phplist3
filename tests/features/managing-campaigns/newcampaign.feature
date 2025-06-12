@wip
Feature: Create new campaign

    In order to create a new campaign
    As an admin user
    I need to be able to login and start a new campaign

    Scenario: Login, create and send a campaign
        Given I have logged in as an administrator
        Given I follow "Campaigns"
        Given I follow "Send a campaign"
        Then I must see "Start a new campaign"
        Given I follow "Start a new campaign"
        Then I must see "Campaign subject"
        When I fill in "subject" with "This is a test subject"
        And I fill in "fromfield" with "From me me@mydomain.com"
        And I fill in "sendmethod" with "inputhere"
        And I fill in "message" with "This is the Content of the Campaign"
        And I fill in "footer" with "This is the Footer of the campaign"
        And I fill in "campaigntitle" with "This is the Title of the Campaign"
        And I press "Save and continue editing"
        Then I must see "This is the Content of the Campaign"
        When I follow "Scheduling"
        And I refresh the page
        Then I must see "Embargoed Until"
        When I follow "Lists"
        And I refresh the page
        Then I must see "Please select the lists you want to send your campaign to"
        And I must see "All Lists"
        When I check "targetlist[all]"
        And I press "Save and continue editing"
 #       And I refresh the page
        Then I must see "selected"
        When I follow "Finish"
        And I press "send"
        Then I must see "Campaign queued"


    Scenario: Send a campaign with missing subject and/content
        Given I have logged in as an administrator
        Given I follow "Campaigns"
        Given I follow "Send a campaign"
        Given I follow "Start a new campaign"
        And I follow "Finish"
        Then I must see "Some required information is missing. The send button will be enabled when this is resolved."
        Given I go back to "Content"
   #     And I enter text "some content"
        And I fill in "message" with "some content"
        Given I follow "Finish"
        Then I must see "Some required information is missing. The send button will be enabled when this is resolved."
        Given I go back to "Content"
        And I fill in "subject" with "Campaign subject"
        And I follow "Finish"
        Then I must see "destination lists missing"
        Given I follow "Lists"
        And I refresh the page
        # Try with and without the colon
        Then I must see "Please select the lists you want to send your campaign to:"
        And the "targetlist[all]" checkbox should not be checked
        And the "targetlist[allactive]" checkbox should not be checked
        When I check "targetlist[all]" 
        And I follow "Finish"
        And I press "Place Campaign in Queue for Sending"
        Then I must see "Campaign queued"

    
    Scenario: Send test campaign when email is not on the database
        Given I have logged in as an administrator
        Given I follow "Campaigns"
        Given I follow "Send a campaign"
        When I follow "Start a new campaign"
        Then I must see "Campaign subject"
        When I fill in "subject" with "This is a test subject"
        And I fill in "fromfield" with "From me me@mydomain.com"
        And I fill in "sendmethod" with "inputhere"
#        And I enter text "some content"
        And I fill in "message" with "some content"
        And I fill in "campaigntitle" with "This is the Title of the Campaign"
        And I fill in "testtarget" with "newemail5@domain.com"
        And I press "sendtest"
        Then I must see "Email address not found to send test message.:"
        And I must see "add"
        




