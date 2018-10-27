Feature: SMTP Mail test

    @emptySentMail
    Scenario: Sending a test email
        Given I have logged in as an administrator
        And I follow "Campaigns"
        And I follow "Send a campaign"
        And I follow "start a new campaign"
        Then I should see "SEND A CAMPAIGN"
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
        Then I should have email sent from address "me@mydomain.com"
        And I should have email sent from "From Me"
        And I should have email sent to address "admin@phplist.dev"
        And I should have email sent contains "This is the Content of the Campaign"
        # @todo: remove this failed test before pull request merged
        And I should have email sent with the following:
            | from         | Failed Name             |
            | from address | fail.from@mydomain.com  |
            | to address   | fail.to@pplist.dev      |

    @emptySentMail @javascript
    Scenario: Sending campaign with list subscribers
        Given I have logged in as an administrator
        And I have "smtp" list with the following subscribers:
            | test1@phplist.dev |
            | test2@phplist.dev |
            | test3@phplist.dev |
        And I follow "Campaigns"
        And I follow "Send a campaign"
        And I follow "start a new campaign"
        Then I should see "SEND A CAMPAIGN"
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
        And I check "smtp" as target list
        And I press "Save and continue editing"
        When I follow "Finish"
        And I press "send"
        Then I should see "Campaign added"
        And I should see "Campaign queued"
        When I follow "process queue"
        Then I should see "Processing queued campaigns"
        When I follow "Send the queue"
        And I wait for the ajax response
        Then I should see "Processing queued campaigns"
        And I should have email sent from "From Me"
        And I should have email sent from address "me@mydomain.com"
        And I should have email sent to address "test1@phplist.dev"
        And I should have email sent to address "test2@phplist.dev"
        And I should have email sent to address "test3@phplist.dev"
        And I should have email sent contains "This is the Content of the SMTP Campaign"
        And I should have email sent contains "This is the Footer of the campaign"

