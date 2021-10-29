@api @group-e
Feature: Plus button
  In order be able to create content
  As a user of the website
  I need to be able see the plus button only when it has actions available.

  Scenario: Plus button shown when actions are available.
    Given the following solution:
      | title       | Simple solutions                         |
      | description | Conplex problems call for easy solutions |
      | state       | validated                                |

    When I am logged in as a facilitator of the "Simple solutions" solution
    And I go to the homepage of the "Simple solutions" solution
    And I click "Add document" in the plus button menu

    Then I am an anonymous user
    And I go to the homepage of the "Simple solutions" solution
    # Plus button is not shown.
    Then I should not see the plus button menu
