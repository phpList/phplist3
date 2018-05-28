Feature: Navigate the app using the menu
  In order to access different application pages
  As an admin user
  I need to be able to view and navigate navigation menu links

  Scenario Outline: Use menu navigation links
    Given I have logged in as an administrator
    Then I should see "<Pagename>"
    
    Examples:
      | Pagename                  |
      | Search subscribers        |
      | Add a new subscriber      |
      | Manage subscribers        |
      | Subscriber lists          |
      | Import emails             |
      | Export subscribers        |
      | View bounces per list     |
      | Suppression List          |
      | Reconcile subscribers     |
      | Send a campaign           |
      | List of campaigns         |
      | Manage campaign templates |
      | Statistics overview |
      | View opens |
      | Campaign click statistics |
      | URL click statistics |
      | Domain statistics |
      | Update translations |
      | Verify the DB structure |
      | Log of events |
      | Initialise the database |
      | Upgrade phpList |
      | Manage bounces |
      | Send the queue |
      | Rebuild DB indexes |
      | system |
      | Configuration |
      | Settings |
      | Manage plugins |
      | Configure attributes |
      | Subscribe pages |
      | Manage administrators |
      | Import administrators |
      | Configure administrator attributes |
      | Bounce rules |
      | Check bounce rules |
      | Categorise lists |
      | About phpList |
      | Help |
      | Subscriber statistics |
      | tests |
