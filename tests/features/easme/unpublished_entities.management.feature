@api
Feature:
  Management of unpublished content.

  Scenario: Proposed challenges are aggregated in a screen for the moderator.
    Given the following collections:
      | title       | state     |
      | Challenge 1 | proposed  |
      | Challenge 2 | proposed  |
      | Challenge 3 | validated |
      | Challenge 4 | proposed  |
      | Challenge 5 | validated |
    And the following solutions:
      | title      | collection  | state     |
      | Solution 1 | Challenge 1 | proposed  |
      | Solution 2 | Challenge 3 | validated |
      | Solution 3 | Challenge 3 | proposed  |
      | Solution 4 | Challenge 3 | validated |
      | Solution 5 | Challenge 5 | proposed  |
      | Solution 6 | Challenge 5 | draft     |

    When I am logged in as a user with the authenticated role
    And I go to "/dashboard/proposed-groups"
    Then I should get an access denied error

    When I am logged in as a moderator
    And I go to "/dashboard/proposed-groups"
    Then I should see the link "Challenge 1"
    And I should see the link "Challenge 2"
    And I should see the link "Challenge 4"
    And I should see the link "Solution 3"
    And I should see the link "Solution 5"
    And I should see the link "Solution 1"

    But I should not see the link "Challenge 3"
    And I should not see the link "Solution 2"
    And I should not see the link "Solution 4"
    And I should not see the link "Solution 6"

  Scenario: Pledges can be found in an admin page for each group.
    Given the following collections:
      | title       | state     |
      | Challenge 1 | validated |
    And the following solutions:
      | title      | collection  | state     |
      | Solution 1 | Challenge 1 | validated |
      | Solution 2 | Challenge 1 | validated |
    And pledge content:
      | title    | description           | solution   | state     | status      |
      | Pledge 1 | We would like to help | Solution 1 | proposed  | unpublished |
      | Pledge 2 | We would like to help | Solution 1 | validated | published   |
      | Pledge 3 | We would like to help | Solution 1 | proposed  | unpublished |
      | Pledge 4 | We would like to help | Solution 2 | validated | published   |
      | Pledge 5 | We would like to help | Solution 2 | proposed  | unpublished |

    When I am logged in as a moderator
    And I go to the "Solution 1" solution
    And I click "Manage content"
    Then I should see the link "Pledge 1"
    And I should see the link "Pledge 3"
    And I should see the link "Pledge 2"
    But I should not see the link "Pledge 4"
    And I should not see the link "Pledge 5"

    When I go to the "Solution 2" solution
    And I click "Manage content"
    Then I should see the link "Pledge 4"
    And I should see the link "Pledge 5"
    But I should not see the link "Pledge 1"
    And I should not see the link "Pledge 2"
    And I should not see the link "Pledge 3"
