@api @email
Feature:
  In order to efficiently mange users
  As a moderator of the website
  I need to be able to cancel user accounts

  Background:
    Given users:
      | Username      | Roles     | E-mail                   | First name | Family name |
      | Robin Kelley  | Moderator | RobinKelley@example.com  | Robin      | Kelley      |
      | Hazel Olson   |           | HazelOlson@example.com   | Hazel      | Olson       |
      | Amelia Barker |           | AmeliaBarker@example.com | Amelia     | Barker      |
      | Alicia Potter |           | AliciaPotter@example.com | Alicia     | Potter      |
    And collections:
      | title                   | state     |
      | Lugia was just released | validated |
      | Articuno is hunted      | validated |
    # Assign facilitator role in order to allow creation of a solution.
    # In UAT this can be done by creating the collection through the UI
    # with the related user.
    And the following collection user memberships:
      | collection              | user          | roles                      |
      | Lugia was just released | Hazel Olson   | administrator, facilitator |
      | Articuno is hunted      | Amelia Barker | administrator, facilitator |

  Scenario: Canceling users that are the sole owners of collections cannot be done.
    When I am logged in as a moderator
    And I click "People"
    And I check "Hazel Olson"
    And I check "Amelia Barker"
    And I select "Cancel the selected user account(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the text "User Hazel Olson cannot be deleted as it is currently the sole owner of these collections:"
    And I should see the text "User Amelia Barker cannot be deleted as it is currently the sole owner of these collections:"
    And I should see the link "Lugia was just released"
    And I should see the link "Articuno is hunted"

  @javascript
  Scenario: A moderator deletes a user.
    When all e-mails have been sent
    And I am logged in as a moderator
    And I click "People"
    And I click "Alicia Potter"
    And I click "Edit" in the "Entity actions" region
    And I press "Cancel account"
    And I select the radio button "Delete the account and its content." with the id "edit-user-cancel-method-user-cancel-delete"
    And I check "Notify user when account is canceled"
    And I press "Cancel account"
    And I wait for the batch job to finish
    And the following system email should have been sent:
      | recipient_mail | AliciaPotter@example.com                                                                                                                                                                                                                                                |
      | subject        | Your account has been deleted.                                                                                                                                                                                                                                          |
      | body           | Your account Alicia Potter has been deleted. This action has been done in the framework of moderation activities regularly conducted on the Joinup platform. If you believe that this action has been performed by mistake, please contact the Joinup Community team at |

  @javascript
  Scenario: Delete own account.
    When I am logged in as "Alicia Potter"
    And all e-mails have been sent
    And I visit "/user"
    And I click "Edit" in the "Entity actions" region
    And I press "Cancel account"
    And I press "Cancel account"
    And the following system email should have been sent:
      | recipient | Alicia Potter                                                      |
      | subject   | Account cancellation request for Alicia Potter at Joinup           |
      | body      | by clicking this link or copying and pasting it into your browser: |
    # Click the confirmation link in the email.
    And I click the delete confirmation link for the user "Alicia Potter" from the last email
    And I wait for the batch job to finish
    Then the following system email should have been sent:
      | recipient_mail | AliciaPotter@example.com                                                                                                                                 |
      | subject        | Your account has been deleted.                                                                                                                           |
      | body           | Your account Alicia Potter has been deleted.  If you believe that this action has been performed by mistake, please contact the Joinup Community team at |
