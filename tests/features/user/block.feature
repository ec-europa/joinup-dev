@api
Feature: Block users
  As a moderator I must be able to block users.

  Scenario: A moderator can block a user.
    Given users:
      | Username     | Roles     | E-mail                   |
      | Mod Murielle | Moderator | mod.murielle@example.com |
      | Liam Lego    |           | liam.lego@example.com    |
    # Search user
    And I am logged in as "Mod Murielle"
    Given I am on the homepage
    When I click "People"
    And I fill in "Name or email contains" with "Liam Lego"
    And I press the "Filter" button

    # Block the user
    And all e-mails have been sent
    Then I check "Liam Lego"
    And I select "Block the selected user(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Block the selected user(s) was applied to 1 item."
    And the following email should have been sent:
      | recipient | Liam Lego                                                                                                                 |
      | subject   | Your account was just blocked.                                                                                            |
      | body      | Your Joinup account was recently blocked by our moderators. For more information about blocked accounts, please visit our |

    # Unblock the user
    When all e-mails have been sent
    Then I check "Liam Lego"
    Then I select "Unblock the selected user(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Unblock the selected user(s) was applied to 1 item."
    And the following email should have been sent:
      | recipient | Liam Lego                                                                                                                       |
      | subject   | Your Joinup account has been activated.                                                                                         |
      | body      | Your account at Joinup has been activated. You may now log in by clicking this link or copying and pasting it into your browser |
