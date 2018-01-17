@api
Feature: Invite members to subscribe to discussions
  In order to promote discussion of topics
  As a discussion author or moderator
  I need to be able to invite users to discussions

  @email @javascript
  Scenario: Invite members to a discussion
    Given users:
      | Username         | E-mail                           | First name | Family name |
      | Lynwood Crawford | lcrawford@example.com            | Lynwood    | Crawford    |
      | Glory Ruskin     | gloruskin.hr@example.com         | Glory      | Ruskin      |
      | paternoster      | shaquila.paternoster@example.com | Shaquila   | Paternoster |
      | theacuteone      | hargrave.hr@example.com          | Shannon    | Hargrave    |
    And the following solution:
      | title       | Stainless Steel Siphons |
      | description | Will last forever       |
      | state       | validated               |
    And the following solution user membership:
      | solution                | user         | roles       |
      | Stainless Steel Siphons | theacuteone  | member      |
      | Stainless Steel Siphons | Glory Ruskin | facilitator |
    And the following collection:
      | title       | The Siphon Community |
      | description | We just love siphons |
      | state       | validated            |
    And the following collection user membership:
      | collection           | user             | roles       |
      | The Siphon Community | Lynwood Crawford | member      |
      | The Siphon Community | theacuteone      | member      |
      | The Siphon Community | Glory Ruskin     | facilitator |
    And discussion content:
      | title                            | content                            | author           | state     | collection           | solution                |
      | For your lifetime                | Are you kidding?                   | Lynwood Crawford | validated |                      | Stainless Steel Siphons |
      | Concerned about dissolved gases? | Gas might get trapped in a siphon. | Lynwood Crawford | validated | The Siphon Community |                         |

    # Check that only moderators and the discussion owner can invite members.
    # Anonymous users cannot invite users.
    Given I am not logged in
    And I go to the "For your lifetime" discussion
    Then I should not see the link "Invite"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should not see the link "Invite"

    # Users not a members of the collection/solution cannot invite other users.
    Given I am logged in as "paternoster"
    And I go to the "For your lifetime" discussion
    Then I should not see the link "Invite"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should not see the link "Invite"

    # Regular members of the collection/solution cannot invite other users.
    Given I am logged in as "theacuteone"
    And I go to the "For your lifetime" discussion
    Then I should not see the link "Invite"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should not see the link "Invite"

    # Facilitators can invite users.
    Given I am logged in as "Glory Ruskin"
    And I go to the "For your lifetime" discussion
    Then I should see the link "Invite"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should see the link "Invite"

    # Moderators can invite users.
    Given I am logged in as a "moderator"
    And I go to the "For your lifetime" discussion
    Then I should see the link "Invite"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should see the link "Invite"

    # The discussion owner can invite users.
    Given I am logged in as "Lynwood Crawford"
    And I go to the "For your lifetime" discussion
    Then I should see the link "Invite"
    And I go to the "Concerned about dissolved gases?" discussion
    Then I should see the link "Invite"

    # Navigate to the form.
    When I click "Invite"
    Then I should see the heading "Invite to discussion"

    # Verify that a message is shown when no users are selected and we try to submit the form.
    When I press "Invite to discussion"
    Then I should see the error message "Please add at least one user."

    # Try to filter by first name.
    When I fill in "Name/username/email" with "sha"
    Then I wait until the page contains the text "Shaquila Paternoster (paternoster)"
    And I should see the text "Shannon Hargrave (theacuteone)"
    But I should not see the text "Lynwood Crawford (Lynwood Crawford)"
    And I should not see the text "Glory Ruskin (Glory Ruskin)"

    ## Try to filter by last name.
    When I fill in "Name/username/email" with "raw"
    Then I wait until the page contains the text "Lynwood Crawford (Lynwood Crawford)"
    But I should not see the text "Shaquila Paternoster (paternoster)"
    And I should not see the text "Shannon Hargrave (theacuteone)"
    And I should not see the text "Glory Ruskin (Glory Ruskin)"

    ## Try to filter by e-mail address.
    When I fill in "Name/username/email" with "hr"
    Then I wait until the page contains the text "Glory Ruskin (Glory Ruskin)"
    And I should see the text "Shannon Hargrave (theacuteone)"
    But I should not see the text "Lynwood Crawford (Lynwood Crawford)"
    And I should not see the text "Shaquila Paternoster (paternoster)"

    ## Try fo filter by username.
    When I fill in "Name/username/email" with "acute"
    Then I wait until the page contains the text "Shannon Hargrave (theacuteone)"
    But I should see the text "Glory Ruskin (Glory Ruskin)"
    And I should not see the text "Lynwood Crawford (Lynwood Crawford)"
    And I should not see the text "Shaquila Paternoster (paternoster)"

    ## Try to filter on a combination of first name and last name.
    When I fill in "Name/username/email" with "or"
    Then I wait until the page contains the text "Lynwood Crawford (Lynwood Crawford)"
    And I should see the text "Glory Ruskin (Glory Ruskin)"
    But I should not see the text "Shannon Hargrave (theacuteone)"
    And I should not see the text "Shaquila Paternoster (paternoster)"

    When I fill in "Name/username/email" with "gloruskin.hr@example.com"
    And I hit enter in the keyboard on the field "Name/username/email"
    And I wait for AJAX to finish
    Then the page should show only the chips:
      | Glory Ruskin |
    When I fill in "Name/username/email" with "lcrawford@example.com"
    And I hit enter in the keyboard on the field "Name/username/email"
    And I wait for AJAX to finish
    Then the page should show the chips:
      | Lynwood Crawford |
      | Glory Ruskin     |

    # Delete a chip.
    When I press the remove button on the chip "Lynwood Crawford"
    And I wait for AJAX to finish
    Then the page should show only the chips:
      | Glory Ruskin |

    # Add another one.
    When I fill in "Name/username/email" with "shaquila.paternoster@example.com"
    And I hit enter in the keyboard on the field "Name/username/email"
    And I wait for AJAX to finish
    Then the page should show the chips:
      | Shaquila Paternoster |
      | Glory Ruskin         |

    # Invite some users.
    Given the mail collector cache is empty
    And I press "Invite to discussion"
    Then I should see the success message "2 user(s) have been invited to this discussion."
    And the following email should have been sent:
      | recipient | Glory Ruskin                                                                                    |
      | subject   | You are invited to subscribe to a discussion.                                                   |
      | body      | Lynwood Crawford invites you to participate in the discussion Concerned about dissolved gases?. |
    And the following email should have been sent:
      | recipient | paternoster                                                                                     |
      | subject   | You are invited to subscribe to a discussion.                                                   |
      | body      | Lynwood Crawford invites you to participate in the discussion Concerned about dissolved gases?. |
    And 2 e-mails should have been sent

    # Try if it is possible to resend an invitation.
    Given the mail collector cache is empty
    When I fill in "Name/username/email" with "gloruskin.hr@example.com"
    And I hit enter in the keyboard on the field "Name/username/email"
    And I wait for AJAX to finish
    Then the page should show only the chips:
      | Glory Ruskin |
    When I press "Invite to discussion"
    Then I should see the success message "The invitation was resent to 1 user(s) that were already invited previously but haven't yet accepted the invitation."
    And the following email should have been sent:
      | recipient | Glory Ruskin                                                                                    |
      | subject   | You are invited to subscribe to a discussion.                                                   |
      | body      | Lynwood Crawford invites you to participate in the discussion Concerned about dissolved gases?. |

    # Accept an invitation by clicking on the link in the e-mail.
    # Initially there should not be any subscriptions.
    And the "Concerned about dissolved gases?" discussion should have 0 subscribers
    Given I am logged in as "Glory Ruskin"
    When I accept the invitation for the "Concerned about dissolved gases?" discussion
    Then I should see the heading "Concerned about dissolved gases?"
    And I should see the success message "You have been subscribed to this discussion."
    And the "Concerned about dissolved gases?" discussion should have 1 subscriber

    # Try to invite the user again. This should not send an invitation since the
    # user is already subscribed.
    Given the mail collector cache is empty
    And I am logged in as "Lynwood Crawford"
    When I go to the "Concerned about dissolved gases?" discussion
    And I click "Invite"
    And I fill in "Name/username/email" with "gloruskin.hr@example.com"
    And I hit enter in the keyboard on the field "Name/username/email"
    And I wait for AJAX to finish
    Then the page should show only the chips:
      | Glory Ruskin |
    When I press "Invite to discussion"
    Then I should see the success message "1 user(s) were already subscribed to the discussion. No new invitation was sent."
    And 0 e-mails should have been sent
    And the "Concerned about dissolved gases?" discussion should have 1 subscriber
