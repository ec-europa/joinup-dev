@api @tallinn
Feature: Display of reports for the Tallinn initiative.
  In order to consult the Tallinn reports
  As a privileged user
  I need to be able to see the reports I have access to.

  Scenario: Only the report entries are shown in the "Implementation monitoring" page.
    Given users:
      | Username     |
      | Jayda Ingham |
    And the following collection user memberships:
      | collection                      | user         | roles       |
      | Tallinn Ministerial Declaration | Jayda Ingham | facilitator |
    Given news content:
      | title           | collection                      | state     |
      | Results are out | Tallinn Ministerial Declaration | validated |
    And document content:
      | title                                  | collection                      | state     |
      | Ministerial Declaration on eGovernment | Tallinn Ministerial Declaration | validated |
    And tallinn_report content:
      | title        | author       | collection                      |
      | Italy report | Jayda Ingham | Tallinn Ministerial Declaration |

    # All content except reports should be shown in the collection overview.
    Given I am logged in as "Jayda Ingham"
    When I go to the "Tallinn Ministerial Declaration" collection
    Then I should see the "Results are out" tile
    And I should see the "Ministerial Declaration on eGovernment" tile

    # Only report content should be shown in the initiative page.
    When I click "Implementation monitoring" in the "Left sidebar" region
    Then I should see the text "The above-represented data are provided in tabular format."
    Then I should see the "Italy report" tile
    But I should not see the "Results are out" tile
    And I should not see the "Ministerial Declaration on eGovernment" tile
