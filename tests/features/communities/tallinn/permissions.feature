@api
Feature:
  - As a user, author of a Tallinn Report node, I want to be able to edit the
    node that I own but I cannot edit other user's Tallinn Reports.
  - As a moderator, when editing a Tallinn Report node, I want to be able
    edit the author of the node.

  Scenario: Test permissions on Tallinn Reports.
    Given users:
      | Username  | Roles     |
      | vasile    |           |
      | dominique |           |
      | chef      | moderator |
      | gheorghe  |           |
    And collection:
      | title | Kind of Tallinn |
      | state | validated       |
    And the following collection user memberships:
      | collection      | user      |
      | Kind of Tallinn | vasile    |
      | Kind of Tallinn | dominique |
    And tallinn_report content:
      | title          | author    | collection      |
      | Romania Report | gheorghe  | Kind of Tallinn |
      | France Report  | dominique | Kind of Tallinn |

    # A user outside of Tallinn collection is not able to edit any report.
    Given I am logged in as gheorghe
    When I visit the tallinn_report content "Romania Report" edit screen
    Then I should get a 403 HTTP response
    When I visit the tallinn_report content "France Report" edit screen
    Then I should get a 403 HTTP response

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

    # A Tallinn collection member can change its own report but not other's. In
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
