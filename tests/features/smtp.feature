@wip
Feature: SMTP Mail test

    Scenario: Sending a test email
        Given I have logged in as an administrator
        And I follow "Campaigns"
        And I follow "Send a campaign"
        And I follow "Start a new campaign"
        Then I must see "Send a campaign"
        When I fill in "subject" with "This is a test subject"
        And I fill in "fromfield" with "From Me me@mydomain.com"
        And I fill in "sendmethod" with "inputhere"
        And I fill in "message" with "This is the Content of the Campaign"
        And I fill in "footer" with "This is the Footer of the campaign"
        And I fill in "campaigntitle" with "This is the Title of the Campaign"
        And I press "Save and continue editing"
        And I fill in "testtarget" with "admin@phplist.dev"
        And I press "sendtest"
        And I wait for 1 seconds
        Then I must see "Success"
#        Then I should have email sent from address "me@mydomain.com"
#        And I should have email sent from "From Me"
#        And I should have email sent to address "admin@phplist.dev"
#        And I should have email sent contains "This is the Content of the Campaign"

    Scenario: Sending campaign with list subscribers
        Given I have logged in as an administrator
        And I follow "Subscribers"
        And I follow "Subscriber lists"
        And I follow "Add a list"
        And I fill in "listname" with "my test list"
        And I fill in "description" with "This is a test list, created with Behat"
        And I press "Save"
        And I follow "Add some subscribers"
        And I fill in "importcontent" with 3 emails
        And I press "doimport"
        Then I must see "3 lines processed"
        And I follow "Campaigns"
        And I follow "Send a campaign"
        And I follow "Start a new campaign"
        Then I must see "Send a campaign"
        When I fill in "subject" with "This is a SMTP Campaign Subject"
        And I fill in "fromfield" with "From Me me@mydomain.com"
        And I fill in "sendmethod" with "inputhere"
        And I fill in "message" with "This is the Content of the SMTP Campaign"
        And I fill in "footer" with "This is the Footer of the campaign"
        And I fill in "campaigntitle" with "SMTP Campaign Title"
        And I press "Save and continue editing"
        And I follow "Format"
        And I press "Save and continue editing"
        And I follow "Lists"
        And I check "my test list" as target list
        And I press "Save and continue editing"
        When I follow "Finish"
        And I press "Send"
        Then I must see "Campaign added"
        And I must see "Campaign queued"
        When I follow "View progress"
        Then I must see "submitted"

#        Then I must see "Processing queued campaigns"
#        And I should have email sent from "From Me"
#        And I should have email sent from address "me@mydomain.com"
#        And I should have email sent to address "test1@phplist.dev"
#        And I should have email sent to address "test2@phplist.dev"
#        And I should have email sent to address "test3@phplist.dev"
#        And I should have email sent contains "This is the Content of the SMTP Campaign"
#        And I should have email sent contains "This is the Footer of the campaign"

