@api @terms @group-f
Feature: Owner moderation
  In order to manage owners
  As a user of the website
  I need to be able to transit the owners from one state to another.

  @terms @uploadFiles:logo.png,banner.jpg
  Scenario: Publish, update, request changes, publish again and ask to delete an owner.
    Given users:
      | Username        |
      | Raeburn Hibbert |
    And owner:
      | name             | type                    |
      | Good food eaters | Non-Profit Organisation |

    When I am logged in as "Raeburn Hibbert"
    And I go to the propose collection form
    And I fill in the following:
      | Title  | The healthy food European project |
      # Contact information data.
      | Name   | Duche Baggins                     |
      | E-mail | duche.baggins@example.com         |
    And I enter "Keep Europe healthy through healthy eating." in the "Description" wysiwyg editor
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I click the 'Categorisation' tab
    And I select "EU and European Policies" from "Topic"

    # An authenticated user can create an owner in published state.
    When I click the 'Additional fields' tab
    And I press "Add new" at the "Owner" field
    And I set the Owner type to "Academia/Scientific organisation"
    And I fill in "Name" with "EU healthy movement"
    When I press "Create owner"
    Then I should see "EU healthy movement"

    # Save the collection to finalise the creation of the owner entity.
    When I press "Propose"
    Then I should see the heading "The healthy food European project"

    # Make a change to the owner entity.
    When I go to the homepage of the "EU healthy movement" owner
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Owner EU healthy movement"
    And the following 2 buttons should be present "Update, Request deletion"
    And the current workflow state should be "Validated"
    And I should not see the link "Delete"
    When I fill in "Name" with "EU healthy group"
    And I press "Update"
    Then I should see the heading "EU healthy group"

    # Request an update as moderator: the chosen owner type is wrong.
    When I am logged in as a moderator
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Owner EU healthy group"
    And the following 3 buttons should be present "Update, Request changes, Request deletion"
    And the current workflow state should be "Validated"
    # Deletion should not be possible as the owner entity is referenced by the collection.
    And I should not see the link "Delete"
    And I press "Request changes"
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
    And the following 1 button should be present "Update"
    And the current workflow state should be "Needs update"
    And I should not see the link "Delete"
    # Do the changes.
    When I set the Owner type to "Non-Governmental Organisation"
    And I press "Update"
    Then I should see the heading "EU healthy group"

    # The moderator approves the changes.
    When I am logged in as a moderator
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    And the following 2 buttons should be present "Update, Approve changes"
    And the current workflow state should be "Needs update"
    And I press "Approve changes"
    Then I should see the heading "EU healthy group"

    # The facilitator asks for deletion. This is rejected since the owner is
    # used by the collection.
    When I am logged in as "Raeburn Hibbert"
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    And the following 2 buttons should be present "Update, Request deletion"
    And the current workflow state should be "Validated"
    And I press "Request deletion"
    Then I should see the error message 'The owner cannot be deleted since it owns the following collections: "The healthy food European project". Please set a different owner for these collections before requesting deletion.'

    # Also a moderator cannot delete the owner as it's used by the collection.
    When I am logged in as a moderator
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then I should not see the link "Delete"

    # Change the owner in the collection.
    When I go to the homepage of the "The healthy food European project" collection
    And I click "Edit"
    And I click the 'Additional fields' tab
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

    # Now the facilitator can request deletion.
    When I am logged in as "Raeburn Hibbert"
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    And the following 2 buttons should be present "Update, Request deletion"
    And the current workflow state should be "Validated"
    And I press "Request deletion"
    Then I should see the heading "EU healthy group"
    And I should not see the error message containing 'The owner cannot be deleted'

    # Now the moderator is able to delete the old owner.
    When I am logged in as a moderator
    And I go to the homepage of the "EU healthy group" owner
    And I click "Edit" in the "Entity actions" region
    Then I should see the link "Delete"
    When I click "Delete"
    # Confirm the deletion.
    And I press "Delete"
    Then I should not see the link "EU healthy group"

    # Final cleanup.
    Then I delete the "The healthy food European project" collection
    And I delete the "Duche Baggins" contact information
