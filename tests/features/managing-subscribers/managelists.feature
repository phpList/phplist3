Feature: Create, edit, categorize and delete lists
  In order for my subscribers to be organized
  As an admin user
  I need to be able to login and create new lists, edit the lists I have, categorize lists and remove the ones I don't need

 Scenario: Add a new list
           Given I have logged in as an administrator
           Given I follow "Subscribers"
           Given I follow "Subscriber lists"
           Then I should see "Categorise lists" 
           And I should see "Add a list"
           Given I follow "Add a list"
           Then I should see "List name:"
           Given I fill in "listname" with "test list 1"
           And I check "active"
           And I press "Save"
           Then I should see "Add some subscribers" 
           And I should see "Add another list"
           Given I follow "Subscriber lists"
           Then I should see "test list 1"

 Scenario: Add a category
           Given I have logged in as an administrator
           Given I follow "Subscribers"
           Given I follow "Subscriber lists"
           Then I should see "Categorise lists" 
           Given I click over "Categorise lists"
           Then I should see "Configure categories"
           Given I follow "Configure categories"
           Then I should see "Editing Categories for lists. Separate with commas."
           When I write "First Category, Second Category" into "edit_list_categories"
           And I press "savebutton"
           And I press "savebutton"
           Then I should see "Categories saved"

 Scenario: Edit a list
           Given I have logged in as an administrator
           Given I follow "Subscribers"
           Given I follow "Subscriber lists"
           And I follow "View members of this list"
           Then I should see "Edit list details"
           When I follow "Edit list details"
           Then I should see "Edit a list"
           When I fill in "listname" with "modifiedname"
           And I press "Save"
           And I follow "Subscriber lists"
           Then I should see "modifiedname"


# Scenario: Delete a list
#           Given I have logged in as an administrator
#           Given I follow "Subscribers"
#           Given I follow "Subscriber lists"
#           Given I follow "Delete this list"
#           Then I should see "Are you sure you want to delete this list" on popups
#           When I confirm the popup
#           Then I should see "Deleting list"

 
 