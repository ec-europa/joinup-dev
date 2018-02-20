@api
Feature: Collection membership invitations
  In order to grow a community
  As a collection facilitator
  I need to be able to invite members to my collection

  Background:
    Given users:
      | Username       | Roles | E-mail                     | First name | Family name |
      | Pieter Griffin |       | pieter.griffin@example.com | Pieter     | Griffin     |
      | Lois Griffin   |       | lois.griffin@example.com   | Lois       | Griffin     |
      | Bryan Griffin  |       | bryan.griffin@example.com  | Bryan      | Griffin     |
      | Stewie Griffin |       | stewie.griffin@example.com | Stewie     | Griffin     |
      | Meg Griffin    |       | meg.griffin@example.com    | Meg        | Griffin     |
    And the following collections:
      | title           | description        | logo     | banner     | closed | state     |
      | Stewie's family | Any fan of family? | logo.png | banner.jpg | yes    | validated |
    And the following collection user memberships:
      | collection      | user           | roles       | state  |
      | Stewie's family | Pieter Griffin | facilitator | active |
      | Stewie's family | Stewie Griffin |             | active |
      | Stewie's family | Meg Griffin    |             | blocked|

  Scenario:Only privileged members are able to access the invitation page.
    When I am not logged in
    And I go to the "Stewie's family" collection
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add members"

    When I am logged in as an authenticated
    And I go to the "Stewie's family" collection
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add members"

    When I am logged in as "Stewie Griffin"
    And I go to the "Stewie's family" collection
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add members"

    When I am logged in as "Pieter Griffin"
    And I go to the "Stewie's family" collection
    And I click "Members" in the "Left sidebar"
    Then I should see the link "Add members"
    When I click "Add members"
    Then I should see the heading "Add members"

  @email
  Scenario: Facilitators are able to invite users to the collection.
    Given the "Stewie's family" collection should have 0 pending members

    And I am logged in as "Pieter Griffin"
    And I go to the "Stewie's family" collection
    And I click "Members" in the "Left sidebar"
    When I click "Add members"
    # Verify that a message is shown when no users are selected and we try to submit the form.
    When I press "Invite members"
    Then I should see the error message "Please add at least one user."

    When I fill in "E-mail" with "lois.griffin@example.com"
    And I press "Add"
    Then the page should show the chips:
      | Lois Griffin |
    # Verify that an error message is shown when trying to add a mail not
    # present in the system.
    When I fill in "E-mail" with "gerard.griffin@example.com"
    And I press "Add"
    Then I should see the error message "No user found with mail gerard.griffin@example.com."
    # Verify that an error message is shown when trying to add the same
    # user twice.
    When I fill in "E-mail" with "lois.griffin@example.com"
    And I press "Add"
    Then I should see the error message "The user with mail lois.griffin@example.com has been already added to the list."
    # Add some other users.
    When I fill in "E-mail" with "bryan.griffin@example.com"
    And I press "Add"
    Then the page should show the chips:
      | Lois Griffin  |
      | Bryan Griffin |
    # Remove a user.
    When I press the remove button on the chip "Bryan Griffin"
    Then the page should show only the chips:
      | Lois Griffin |
    And I should not see the text "Bryan Griffin"

    # Add the users as members.
    Given the option with text "Member" from select "Role" is selected
    When I press "Invite members"
    Then I should see the success message "An invitation has been sent to the selected users. Their membership is pending."
    And the following email should have been sent:
      | recipient | Lois Griffin                                                                 |
      | subject   | Joinup: You are invited to join the collection "Stewie's family".            |
      | body      | Pieter has invited you to join the collection "Stewie's family" as a member. |
    And "Lois Griffin" should have a pending invitation in the "Stewie's family" collection

    # Check that the same user cannot be invited again.
    And I go to the "Stewie's family" collection
    And I click "Members" in the "Left sidebar"
    And I click "Add members"
    And I fill in "E-mail" with "lois.griffin@example.com"
    And I press "Add"
    Then I should see the error message "There is already an active invitation for Lois Griffin."

    # Reject the invitation.
    When I click the reject invitation link from the last email sent to "Lois Griffin"
    And no invitation should exist for the user "Lois Griffin" in the "Stewie's family" collection

    # Ensure that the accept/reject click is not accessible anymore.
    When I click the accept invitation link from the last email sent to "Lois Griffin"
    Then I should see the heading "Page not found"
    When I click the reject invitation link from the last email sent to "Lois Griffin"
    Then I should see the heading "Page not found"

    # Check that a member cannot be invited again.
    When all e-mails have been sent
    And I go to the "Stewie's family" collection
    And I click "Members" in the "Left sidebar"
    And I click "Add members"
    And I fill in "E-mail" with "stewie.griffin@example.com"
    And I press "Add"
    Then I should see the error message "There is already an active membership for Stewie Griffin in the collection."
    When I fill in "E-mail" with "meg.griffin@example.com"
    And I press "Add"
    Then I should see the error message "There is already a blocked membership for Meg Griffin in the collection."
    # Ensure that no chips are left in the page.
    When I press "Invite members"
    Then I should see the error message "Please add at least one user."

    # Add a facilitator.
    And I fill in "E-mail" with "bryan.griffin@example.com"
    And I press "Add"
    Then the page should show the chips:
      | Bryan Griffin |
    When I select "Facilitator" from "Role"
    And I press "Invite members"
    Then I should see the success message "An invitation has been sent to the selected users. Their membership is pending."
    And "Bryan Griffin" should have a pending invitation in the "Stewie's family" collection
    And the following email should have been sent:
      | recipient | Bryan Griffin                                                                     |
      | subject   | Joinup: You are invited to join the collection "Stewie's family".                 |
      | body      | Pieter has invited you to join the collection "Stewie's family" as a facilitator. |

    # Accept the invitation.
    When I click the accept invitation link from the last email sent to "Bryan Griffin"
    Then I should see the following success messages:
      | You are now a facilitator of the "Stewie's family" collection. |
    And "Bryan Griffin" should have an accepted invitation in the "Stewie's family" collection

    # Ensure that the accept/reject click is not accessible anymore.
    When I click the accept invitation link from the last email sent to "Bryan Griffin"
    Then I should see the text "Access denied"

    # Try new privileges.
    When I am logged in as "Bryan Griffin"
    And I go to the "Stewie's family" collection
    And I click "Members" in the "Left sidebar"
    Then I should see the link "Add members"
    When I click "Add members"
    Then I should see the heading "Add members"
