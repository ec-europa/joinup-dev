@api
Feature: Invite members to subscribe to discussions
  In order to promote discussion of topics
  As a discussion author or moderator
  I need to be able to invite users to discussions

  @email
  Scenario: Invite members to a discussion
    Given users:
      | Username            | E-mail                   | First name | Family name  |
      | Viktor Bhattacharya | whackybhatta@example.com | Viktor     | Bhattacharya |
      | Vikentiy Rozovsky   | v.rozovsky@example.com   | Vikentiy   | Rozovsky     |
      | Roxanne Stavros     | whackyroxy@example.com   | Roxanne    | Stavros      |
      | Rodrigo Villanueva  | r.villanueva@example.com | Rodrigo    | Villanueva   |
    And the following collection:
      | title             | The Siphon Community |
      | description       | We just love siphons |
      | state             | validated            |
    And the following collection user membership:
      | collection           | user                | roles       |
      | The Siphon Community | Viktor Bhattacharya | member      |
      | The Siphon Community | Rodrigo Villanueva  | member      |
      | The Siphon Community | Vikentiy Rozovsky   | facilitator |
    And discussion content:
      | title                            | content                            | author              | state     | collection           |
      | Concerned about dissolved gases? | Gas might get trapped in a siphon. | Viktor Bhattacharya | validated | The Siphon Community |

    # Check that only moderators and the discussion owner can invite members.
    # Anonymous users cannot invite users.
    Given I am not logged in
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should not see the link "Invite"

    # Users that are not a member of the collection cannot invite users.
    Given I am logged in as "Roxanne Stavros"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should not see the link "Invite"

    # Users that are a regular member of the collection cannot invite users.
    Given I am logged in as "Rodrigo Villanueva"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should not see the link "Invite"

    # Facilitators can invite users.
    Given I am logged in as "Vikentiy Rozovsky"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should see the link "Invite"

    # Moderators can invite users.
    Given I am logged in as a "moderator"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should see the link "Invite"

    # The discussion owner can invite users.
    Given I am logged in as "Viktor Bhattacharya"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should see the link "Invite"

    # Navigate to the form.
    When I click "Invite"
    Then I should see the heading "Invite to discussion"

    # Try to filter by first name.
    When I fill in "Email or name" with "Vik"
    And I press "Filter"
    Then I should see the text "Viktor Bhattacharya (whackybhatta@example.com)"
    And I should see the text "Vikentiy Rozovsky (v.rozovsky@example.com)"
    But I should not see the text "Roxanne Stavros (whackyroxy@example.com)"
    And I should not see the text "Rodrigo Villanueva (r.villanueva@example.com)"

    # Try to filter by last name.
    When I fill in "Email or name" with "attac"
    And I press "Filter"
    Then I should see the text "Viktor Bhattacharya (whackybhatta@example.com)"
    But I should not see the text "Vikentiy Rozovsky (v.rozovsky@example.com)"
    And I should not see the text "Roxanne Stavros (whackyroxy@example.com)"
    And I should not see the text "Rodrigo Villanueva (r.villanueva@example.com)"

    # Try to filter by e-mail address.
    When I fill in "Email or name" with "whacky"
    And I press "Filter"
    Then I should see the text "Viktor Bhattacharya (whackybhatta@example.com)"
    And I should see the text "Roxanne Stavros (whackyroxy@example.com)"
    But I should not see the text "Vikentiy Rozovsky (v.rozovsky@example.com)"
    And I should not see the text "Rodrigo Villanueva (r.villanueva@example.com)"

    # Try to filter on a combination of first name and last name.
    When I fill in "Email or name" with "Ro"
    And I press "Filter"
    Then I should see the text "Vikentiy Rozovsky (v.rozovsky@example.com)"
    And I should see the text "Roxanne Stavros (whackyroxy@example.com)"
    And I should see the text "Rodrigo Villanueva (r.villanueva@example.com)"
    But I should not see the text "Viktor Bhattacharya (whackybhatta@example.com)"

    # Invite some users.
    Given the mail collector cache is empty
    When I check "Vikentiy Rozovsky (v.rozovsky@example.com)"
    And I check "Roxanne Stavros (whackyroxy@example.com)"
    And I press "Invite to discussion"
    Then I should see the success message "2 user(s) have been invited to this discussion."
    And the following email should have been sent:
      | recipient | Vikentiy Rozovsky                                                                                  |
      | subject   | You are invited to subscribe to a discussion.                                                      |
      | body      | Viktor Bhattacharya invites you to participate in the discussion Concerned about dissolved gases?. |
    And the following email should have been sent:
      | recipient | Roxanne Stavros                                                                                    |
      | subject   | You are invited to subscribe to a discussion.                                                      |
      | body      | Viktor Bhattacharya invites you to participate in the discussion Concerned about dissolved gases?. |
    And 2 e-mails should have been sent

    # Try if it is possible to resend an invitation.
    Given the mail collector cache is empty
    When I fill in "Email or name" with "Rozovsky"
    And I press "Filter"
    And I check "Vikentiy Rozovsky (v.rozovsky@example.com)"
    And I press "Invite to discussion"
    Then I should see the success message "The invitation was resent to 1 user(s) that were already invited previously but haven't yet accepted the invitation."
    And the following email should have been sent:
      | recipient | Vikentiy Rozovsky                                                                                  |
      | subject   | You are invited to subscribe to a discussion.                                                      |
      | body      | Viktor Bhattacharya invites you to participate in the discussion Concerned about dissolved gases?. |

    # Accept an invitation by clicking on the link in the e-mail.
    # Initially there should not be any subscriptions.
    And the "Concerned about dissolved gases?" discussion should have 0 subscribers
    Given I am logged in as "Vikentiy Rozovsky"
    When I accept the invitation to participate in the "Concerned about dissolved gases?" discussion
    Then I should see the heading "Concerned about dissolved gases?"
    And I should see the success message "You have been subscribed to this discussion."
    And the "Concerned about dissolved gases?" discussion should have 1 subscriber

    # Try to invite the user again. This should not send an invitation since the
    # user is already subscribed.
    Given the mail collector cache is empty
    And I am logged in as "Viktor Bhattacharya"
    When I go to the "Concerned about dissolved gases?" discussion
    And I click "Invite"
    And I fill in "Email or name" with "Vikentiy"
    And I press "Filter"
    And I check "Vikentiy Rozovsky (v.rozovsky@example.com)"
    And I press "Invite to discussion"
    Then I should see the success message "1 user(s) were already subscribed to the discussion. No new invitation was sent."
    And 0 e-mails should have been sent
    And the "Concerned about dissolved gases?" discussion should have 1 subscriber
