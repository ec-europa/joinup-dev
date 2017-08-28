@api @email
Feature: User role management
  As a moderator I must be able to assign roles to users.

  Scenario: A moderator can assign a role to a user.
    Given users:
      | Username   | Roles     | E-mail                 |
      | Rick Rolls | Moderator | rick.roles@example.com |
      | Nibby Noob |           | nicky.noob@example.com |
    # Search user
    And I am logged in as "Rick Rolls"
    When all e-mails have been sent
    And I am on the homepage
    And I click "People"
    And I fill in "Name or email contains" with "Nibby Noob"
    And I press the "Filter" button
    # Select user and assign role
    And I check "Nibby Noob"
    And I select "Add the Moderator role to the selected users" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Add the Moderator role to the selected users was applied to 1 item."
    And I should see the success message "An e-mail has been send to the user to notify him on the change to his account."
    And the following system email should have been sent:
      | recipient | Nibby Noob                                                                                                |
      | subject   | The Joinup Support Team updated your account for you at Joinup                                         |
      | body      | A moderator has edited your user profile on Joinup. Please check your profile to verify the changes done. |
