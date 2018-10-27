Feature: Create new campaign

    In order to create a new campaign
    As an admin user
    I need to be able to login and start a new campaign

    Scenario: Login and create a campaign
        Given I have logged in as an administrator
        Given I follow "Campaigns"
        Given I follow "Send a campaign"
        Given I follow "start a new campaign"
        Then I should see "Campaign subject"
        When I fill in "subject" with "This is a test subject"
        And I fill in "fromfield" with "From me me@mydomain.com"
        And I fill in "sendmethod" with "inputhere"
        And I fill in "message" with "This is the Content of the Campaign"
        And I fill in "footer" with "This is the Footer of the campaign"
        And I fill in "campaigntitle" with "This is the Title of the Campaign"
        And I press "Save and continue editing"
        Then I should see "This is the Content of the Campaign"
        When I follow "Scheduling"
        Then I should see "Embargoed Until"
        When I follow "Lists"
        Then I should see "Please select the lists you want to send your campaign to"
        And I should see "All Lists"
        When I check "targetlist[all]"
        And I press "Save and continue editing"
        Then I should see "selected"
        When I follow "Finish"
        And I press "send"
        Then I should see "Campaign queued"

    # Switch to using a scenario outline that tests subaccounts also
    Scenario: Select a list to send the campaign to
        Given I have logged in as an administrator
        And I follow "Campaigns"
        And I follow "Send a campaign"
        And I follow "start a new campaign"
        When I follow "Lists"
        # Try with and without the colon
        Then I should see "Please select the lists you want to send your campaign to:"
        And the "targetlist[all]" checkbox should not be checked
        And the "targetlist[allactive]" checkbox should not be checked





