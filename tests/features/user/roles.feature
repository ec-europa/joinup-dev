@api
Feature: User role management
  As a moderator I must be able to assign roles to users.

  Scenario: A moderator can assign a role to a user.
    Given users:
      | Username     | Roles          | E-mail                     |
      | Rick Rolls   | Moderator      | rick.roles@example.com     |
      | Nibby Noob   |                | nicky.noob@example.com     |
    # Search user
    And I am logged in as "Rick Rolls"
    Given I am on the homepage
    When I click "People"
    And I fill in "Name or email contains" with "Nibby Noob"
    And I press the "Filter" button
    # Select user and assign role
    Then I check "Nibby Noob"
    Then I select "Add the Moderator role to the selected users" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Add the Moderator role to the selected users was applied to 1 item."
    Then I should see the success message "An e-mail has been send to the user to notify him on the change to his account."
