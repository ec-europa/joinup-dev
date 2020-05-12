@api @email @group-a
Feature:
  In order to efficiently manage users
  As a moderator of the website
  I need to be able to cancel user accounts

  Background:

    Given users:
      | Username      | Roles     | E-mail                   | First name | Family name |
      | alicia__1997  |           | AliciaPotter@example.com | Alicia     | Potter      |
    And all e-mails have been sent

  Scenario: A moderator deletes a user.
    Given I am logged in as a moderator
    And I click "People"
    And I click "Alicia Potter"
    And I open the header local tasks menu
    And I click "Edit" in the "Header" region
    And I press "Cancel account"
    Then I should see the link "Go back"
    When I press "Cancel account"
    And I wait for the batch process to finish
    And the following email should have been sent:
      | recipient_mail | AliciaPotter@example.com                                                                                                                                                                                                                                            |
      | subject        | Your account has been deleted.                                                                                                                                                                                                                                      |
      | body           | Your account alicia__1997 has been deleted.This action has been done in the framework of moderation activities regularly conducted on the Joinup platform. If you believe that this action has been performed by mistake, please contact The Joinup Support Team at |
    And 1 e-mail should have been sent
    And the blocked "alicia__1997" user exists

  Scenario: A moderator deletes a user using the administrative UI.
    Given I am logged in as a moderator
    And I click "People"

    When I select the "Alicia Potter" row
    And I select "Cancel the selected user account(s)" from "Action"
    And I press "Apply to selected items"

    Then I should see the heading "Are you sure you want to cancel these user accounts?"
    And I should see "Alicia Potter"
    And I should see "This action cannot be undone."

    When I press "Cancel accounts"
    And I wait for the batch process to finish
    Then I should see the success message "Alicia Potter has been deleted."
    And I should not see the link "Alicia Potter"
    And the following email should have been sent:
      | recipient_mail | AliciaPotter@example.com                                                                                                                                                                                                                                            |
      | subject        | Your account has been deleted.                                                                                                                                                                                                                                      |
      | body           | Your account alicia__1997 has been deleted.This action has been done in the framework of moderation activities regularly conducted on the Joinup platform. If you believe that this action has been performed by mistake, please contact The Joinup Support Team at |
    And 1 e-mail should have been sent
    And the "Alicia Potter" user doesn't exist

  Scenario: Delete own account.
    Given I am logged in as "alicia__1997"
    And I visit "/user"
    And I open the header local tasks menu
    And I click "Edit" in the "Header" region
    And I press "Cancel account"
    And I press "Cancel account"
    Then the following email should have been sent:
      | recipient_mail | AliciaPotter@example.com                                           |
      | subject        | Account cancellation request for alicia__1997 at Joinup            |
      | body           | by clicking this link or copying and pasting it into your browser: |
    # Click the confirmation link in the email.
    When I click the delete confirmation link for the user "alicia__1997" from the last email
    And I wait for the batch process to finish
    Then the following email should have been sent:
      | recipient_mail | AliciaPotter@example.com                                                                                 |
      | subject        | Your account has been deleted.                                                                           |
      | body           | If you believe that this action has been performed by mistake, please contact The Joinup Support Team at |
    And 2 e-mails should have been sent
    And the blocked "alicia__1997" user exists
