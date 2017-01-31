@api
Feature: Owner moderation
  In order to manage owners
  As a user of the website
  I need to be able to transit the owners from one state to another.

  Scenario: Publish, update, request changes, publish again and ask to delete an owner.
    Given users:
      | name            |
      | Raeburn Hibbert |
    And owner:
      | name             | type                    |
      | Good food eaters | Non-Profit Organisation |

    When I am logged in as "Raeburn Hibbert"
    And I am on the homepage
    And I click "Propose collection" in the plus button menu
    And I fill in "Title" with "The healthy food European project"
    And I enter "Keep Europe healthy through healthy eating." in the "Description" wysiwyg editor
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I click the 'Categorisation' tab
    And I select "European Policies" from "Policy domain"

    # An authenticated user can create an owner in published state.
    When I click the 'Description' tab
    And I press "Add new" at the "Owner" field
    And I set the Owner type to "Academia/Scientific organisation"
    And I fill in "Name" with "EU healthy movement"
    # The dropdown with the workflow states should not be visible now.
    Then I should not see the text "State"
    When I press "Create owner"
    Then I should see "EU healthy movement"
    # Edit the owner entity and check that the state field is still not visible.
    When I press "Edit" at the "Owner" field
    Then I should not see the text "State"
    # Go back to the main form.
    Then I press "Cancel"

    # Save the collection to finalise the creation of the owner entity.
    When I press "Propose"
    Then I should see the heading "The healthy food European project"

    # Make a change to the owner entity.
    When I go to the homepage of the "EU healthy movement" owner
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Owner EU healthy movement"
    And the "State" select available options should be "Validated, Deletion request"
    And the option "Validated" should be selected
    And I should not see the link "Delete"
    When I fill in "Name" with "EU healthy group"
    And I press "Save"
    Then I should see the heading "EU healthy group"

    # Request an update as moderator: the chosen owner type is wrong.
    When I am logged in as a moderator
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Owner EU healthy group"
    And the "State" select available options should be "Validated, In assessment, Deletion request"
    And the option "Validated" should be selected
    # Deletion should not be possible as the owner entity is referenced by the collection.
    And I should not see the link "Delete"
    # Change the state and save.
    When I select "In assessment" from "State"
    And I press "Save"
    Then I should see the heading "EU healthy group"

    # Another authenticated user should not be allowed to edit the owner entity.
    When I am logged in as an "authenticated user"
    And I go to the homepage of the "EU healthy group" owner
    Then I should not see the link "Edit" in the "Entity actions" region

    # The original owner creator is allowed to update the entity with the
    # requested changes.
    When I am logged in as "Raeburn Hibbert"
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Owner EU healthy group"
    And the "State" select available options should be "In assessment"
    And I should not see the link "Delete"
    # Do the changes.
    When I set the Owner type to "Non-Governmental Organisation"
    And I press "Save"
    Then I should see the heading "EU healthy group"

    # The moderator approves the changes.
    When I am logged in as a moderator
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then the "State" select available options should be "In assessment, Validated"
    And the option "In assessment" should be selected
    When I select "Validated" from "State"
    And I press "Save"
    Then I should see the heading "EU healthy group"

    # The facilitator asks for deletion.
    When I am logged in as "Raeburn Hibbert"
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then the "State" select available options should be "Validated, Deletion request"
    And the option "Validated" should be selected
    When I select "Deletion request" from "State"
    And I press "Save"
    Then I should see the heading "EU healthy group"
    # The facilitator should still be able to edit the owner, so they can undo
    # the deletion request if needed.
    When I click "Edit" in the "Entity actions" region
    Then the "State" select available options should be "Validated, Deletion request"
    And the option "Deletion request" should be selected

    # The moderator cannot still delete the owner as it's referenced.
    When I am logged in as a moderator
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then I should not see the link "Delete"
    And the option "Deletion request" should be selected

    # Change the owner in the collection.
    When I go to the homepage of the "The healthy food European project" collection
    And I click "Edit"
    And I press "Remove" at the "Owner" field
    # Confirm removal.
    And I press "Remove" at the "Owner" field
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Good food eaters"
    And I press "Add owner"
    Then I should see "Good food eaters"
    # Keep the collection as proposed.
    And I press "Propose"
    Then I should see the heading "The healthy food European project"

    # Now the moderator can delete the old owner.
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then I should see the link "Delete"
    When I click "Delete"
    # Confirm the deletion.
    And I press "Delete"
    Then I should not see the link "EU healthy group"

    # Final cleanup.
    Then I delete the "The healthy food European project" collection
