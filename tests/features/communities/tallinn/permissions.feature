@api @tallinn
Feature:
  - As a user, author of a Tallinn Report node, I want to be able to edit the
  node that I own but I cannot edit other user's Tallinn Reports.
  - As a moderator, when editing a Tallinn Report node, I want to be able
  edit the author of the node.

  Background:
    Given users:
      | Username  | Roles     |
      | vasile    |           |
      | dominique |           |
      | chef      | moderator |
      | gheorghe  |           |
      | sherlock  |           |
    And the following collection user memberships:
      | collection          | user      | roles       |
      | Tallinn declaration | vasile    |             |
      | Tallinn declaration | dominique |             |
      | Tallinn declaration | sherlock  | facilitator |
    And tallinn_report content:
      | title          | author    | collection          |
      | Romania Report | gheorghe  | Tallinn declaration |
      | France Report  | dominique | Tallinn declaration |

  Scenario: Test view access on Tallinn Reports.
    # Test that the tallinn tiles are not visible in the overview page.
    Given I am logged in as chef
    When I go to the "Tallinn declaration" collection
    Then I should not see the following lines of text:
      | France Report  |
      | Romania Report |

    # Moderators can see all reports in the Tallinn Initiative page.
    When I click "Tallinn initiative" in the "Left sidebar" region
    Then I should see the following tiles in the correct order:
      | France Report  |
      | Romania Report |

    # Facilitators can see all reports in the Tallinn Initiative page.
    Given I am logged in as "sherlock"
    When I go to the "Tallinn declaration" collection
    And I click "Tallinn initiative" in the "Left sidebar" region
    Then I should see the following tiles in the correct order:
      | France Report  |
      | Romania Report |

    # Each user can only see his report.
    Given I am logged in as "gheorghe"
    When I go to the "Tallinn declaration" collection
    And I click "Tallinn initiative" in the "Left sidebar" region
    Then I should see the following tiles in the correct order:
      | Romania Report |
    But I should not see the text "France Report"

    Given I am logged in as "dominique"
    When I go to the "Tallinn declaration" collection
    And I click "Tallinn initiative" in the "Left sidebar" region
    Then I should see the following tiles in the correct order:
      | France Report |
    But I should not see the text "Romania Report"

  Scenario: Test that the page is showing the results properly.
    # The tallinn facet should not be shown.
    Given I am an anonymous user
    When I go to the "Tallinn declaration" collection
    And I click "Tallinn initiative" in the "Left sidebar" region
    Then I should not see the following facet items "Tallinn reports"

    # Verify that editing a report, does not put it in the last position.
    Given I am logged in as chef
    When I go to the "Tallinn declaration" collection
    And I click "Tallinn initiative" in the "Left sidebar" region
    Then I should see the following tiles in the correct order:
      | France Report  |
      | Romania Report |

    When I click the contextual link "Edit" in the "Romania Report" tile
    And I press "Save"
    And I go to the "Tallinn declaration" collection
    And I click "Tallinn initiative" in the "Left sidebar" region
    Then I should see the following tiles in the correct order:
      | France Report  |
      | Romania Report |

  Scenario: Test permissions on Tallinn Reports.
    # A moderator is able to change any report's author.
    Given I am logged in as chef
    When I visit the tallinn_report content "Romania Report" edit screen
    And I fill in "Authored by" with "dominique"
    When I press "Save"
    Then I should see the error message "The user dominique cannot be set as author of this report as he/she already owns 'France Report'."
    But I fill in "Authored by" with "vasile"
    When I press "Save"
    Then I should see "Tallinn report Romania Report has been updated."
    # Reports cannot be added via UI.
    Given I go to "/node/add/tallinn_report"
    Then I should get a 403 HTTP response

    # A user can change its own report but not other's. In
    # the same time he's not able to change the node owner.
    Given I am logged in as vasile
    When I visit the tallinn_report content "Romania Report" edit screen
    Then I should get a 200 HTTP response
    And the following fields should not be present "Authored by"

    # Set the status to "In progress" but don't fill the "Explanations" field.
    Given I select "In progress" from "Implementation status"
    And I press "Save"
    Then I should see the error message "Action 1: Explanations field is required when the status is In progress."

    # Set the status to "Completed" but don't fill the "Explanations" field.
    Given I select "Completed" from "Implementation status"
    And I press "Save"
    Then I should see the error message "Action 1: Explanations field is required when the status is Completed."

    Given I fill in "Explanations" with "This is done"
    And I press "Save"
    Then I should see "Tallinn report Romania Report has been updated."
    And I should see "This is done"

    # The user cannot edit a report owned by someone else.
    Given I visit the tallinn_report content "France Report" edit screen
    Then I should get a 403 HTTP response
