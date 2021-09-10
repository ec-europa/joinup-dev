@api @group-a
Feature:
  In order to efficiently manage users
  As a moderator of the website
  I need to be able to cancel user accounts

  Background:

    Given users:
      | Username      | Roles     | E-mail                   | First name | Family name |
      | alicia__1997  |           | AliciaPotter@example.com | Alicia     | Potter      |

  Scenario: A moderator cancels a user account.
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
    And the "alicia__1997" user account is cancelled
    And the "Alicia Potter" table row doesn't contain a checkbox

    When I click "Edit" in the "Alicia Potter" row
    Then I should see the warning message "This user account is cancelled. Edit is disabled."
    # On a cancelled account edit form all fields are disabled.
    And the following fields should be disabled "First name,Family name,Allow user to log in via CAS,CAS Username,Email,Username,Password,Confirm password,Photo,Country of origin,Professional domain,Business title,Organisation,Enable the search field,Facebook,GitHub,LinkedIn,SlideShare,Twitter,Vimeo,Youtube,Save"

  Scenario: A moderator deletes a user using the administrative UI.
    Given the following collection:
      | title | Test collection      |
      | state | validated |
    And news content:
      | title     | author       | collection      | state     |
      | News item | alicia__1997 | Test collection | validated |

    Given I am logged in as a moderator
    And I click "People"

    When I select the "Alicia Potter" row
    And I select "Delete selected account(s) and their content" from "Action"
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
    # We cannot assert the number of emails because of created content that
    # sends messages to administrators and moderators. Depending on environment
    # the list of recipients might differ. That's why we're only asserting that
    # a "status blocked" message has not been sent.
    And the following email should not have been sent:
      | template       | status_blocked                 |
      | recipient_mail | AliciaPotter@example.com       |
      | subject        | Your account was just blocked. |
    And the "Alicia Potter" user doesn't exist

    # The content created by an account deleted via admin UI has been deleted.
    When I go to "/collection/test-collection/news/news-item"
    Then the response status code should be 404

  Scenario: Cancel own account.
    Given the following collection:
      | title | Test collection      |
      | state | validated |
    And news content:
      | title     | author       | collection      | state     |
      | News item | alicia__1997 | Test collection | validated |
    And document content:
      | title | author       | collection      | state     |
      | Docky | alicia__1997 | Test collection | validated |
    And discussion content:
      | title  | collection      | state     |
      | Disqus | Test collection | validated |
    And comments:
      | subject  | field_body   | author       | parent |
      | Awesome! | Let's use it | alicia__1997 | Disqus |

    # The content author is clickable.
    When I visit the "News item" news
    Then I should see the link "Alicia Potter"
    When I visit the "Docky" document
    Then I should see the link "Alicia Potter"
    # Check also the comment's author.
    When I visit the "Disqus" discussion
    Then I should see the link "Alicia Potter"

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
    # We cannot assert the number of emails because of created content that
    # sends messages to administrators and moderators. Depending on environment
    # the list of recipients might differ. That's why we're only asserting that
    # a "status blocked" message has not been sent.
    And the following email should not have been sent:
      | template       | status_blocked                 |
      | recipient_mail | AliciaPotter@example.com       |
      | subject        | Your account was just blocked. |
    And the "alicia__1997" user account is cancelled

    # The content author is no more clickable.
    When I visit the "News item" news
    Then I should see "Alicia Potter"
    Then I should not see the link "Alicia Potter"
    When I visit the "Docky" document
    Then I should see "Alicia Potter"
    Then I should not see the link "Alicia Potter"
    When I visit the "Disqus" discussion
    Then I should see "Alicia Potter"
    Then I should not see the link "Alicia Potter"
