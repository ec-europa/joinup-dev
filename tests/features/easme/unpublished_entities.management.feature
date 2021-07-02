@api
Feature:
  Management of unpublished content.

  Scenario: Proposed challenges/solutions/pledges are aggregated in a screen for the moderator.
    Given the following collections:
      | title       | state     | creation date |
      | Challenge 1 | proposed  | 01/01/2021    |
      | Challenge 2 | proposed  | 02/01/2021    |
      | Challenge 3 | validated | 03/01/2021    |
      | Challenge 4 | proposed  | 04/01/2021    |
      | Challenge 5 | validated | 05/01/2021    |
      | Challenge 6 | proposed  | 06/01/2021    |
      | Challenge 7 | proposed  | 07/01/2021    |
      | Challenge 8 | proposed  | 08/01/2021    |
      | Challenge 9 | proposed  | 09/01/2021    |
      | Challenge 0 | proposed  | 10/01/2021    |
    And the following solutions:
      | title       | collection  | state     | creation date |
      | Solution 1  | Challenge 1 | proposed  | 11/02/2021    |
      | Solution 2  | Challenge 3 | validated | 10/02/2021    |
      | Solution 3  | Challenge 3 | proposed  | 09/02/2021    |
      | Solution 4  | Challenge 3 | validated | 08/02/2021    |
      | Solution 5  | Challenge 5 | proposed  | 07/02/2021    |
      | Solution 6  | Challenge 5 | draft     | 06/02/2021    |
      | Solution 7  | Challenge 5 | proposed  | 05/02/2021    |
      | Solution 8  | Challenge 3 | proposed  | 04/02/2021    |
      | Solution 9  | Challenge 5 | proposed  | 03/02/2021    |
      | Solution 01 | Challenge 5 | proposed  | 02/02/2021    |
      | Solution 02 | Challenge 5 | proposed  | 01/02/2021    |
    And pledge content:
      | title     | description           | solution   | state     | status      | created    |
      | Pledge 1  | We would like to help | Solution 1 | proposed  | unpublished | 01/03/2021 |
      | Pledge 2  | We would like to help | Solution 1 | validated | published   | 02/03/2021 |
      | Pledge 3  | We would like to help | Solution 1 | proposed  | unpublished | 03/03/2021 |
      | Pledge 4  | We would like to help | Solution 2 | validated | published   | 04/03/2021 |
      | Pledge 5  | We would like to help | Solution 4 | proposed  | unpublished | 05/03/2021 |
      | Pledge 6  | We would like to help | Solution 2 | proposed  | unpublished | 06/03/2021 |
      | Pledge 7  | We would like to help | Solution 4 | proposed  | unpublished | 07/03/2021 |
      | Pledge 8  | We would like to help | Solution 2 | proposed  | unpublished | 08/03/2021 |
      | Pledge 9  | We would like to help | Solution 4 | proposed  | unpublished | 09/03/2021 |
      | Pledge 00 | We would like to help | Solution 2 | proposed  | unpublished | 10/03/2021 |
      | Pledge 01 | We would like to help | Solution 4 | proposed  | unpublished | 11/03/2021 |

    When I am logged in as a user with the authenticated role
    And I go to "/dashboard"
    Then I should get an access denied error
    And I go to "/dashboard/proposed-challenges"
    Then I should get an access denied error
    And I go to "/dashboard/proposed-solutions"
    Then I should get an access denied error
    And I go to "/dashboard/proposed-pledges"
    Then I should get an access denied error

    When I am logged in as a moderator
    And I go to "/dashboard"
    Then I should see the following links:
      | Challenge 0 |
      | Challenge 9 |
      | Challenge 8 |
      | Challenge 7 |
      | Challenge 6 |
      | Solution 1  |
      | Solution 3  |
      | Solution 5  |
      | Solution 7  |
      | Solution 8  |
      | Pledge 01   |
      | Pledge 00   |
      | Pledge 9    |
      | Pledge 8    |
      | Pledge 7    |
    When I go to "/dashboard/proposed-challenges"
    Then I should see the following links:
      | Challenge 1 |
      | Challenge 2 |
      | Challenge 4 |
      | Challenge 6 |
      | Challenge 7 |
      | Challenge 8 |
      | Challenge 9 |
      | Challenge 0 |
    When I go to "/dashboard/proposed-solutions"
    Then I should see the following links:
      | Solution 02 |
      | Solution 01 |
      | Solution 9  |
      | Solution 8  |
      | Solution 7  |
      | Solution 5  |
      | Solution 3  |
      | Solution 1  |
    When I go to "/dashboard/proposed-pledges"
    Then I should see the following links:
      | Pledge 1  |
      | Pledge 3  |
      | Pledge 5  |
      | Pledge 6  |
      | Pledge 7  |
      | Pledge 8  |
      | Pledge 9  |
      | Pledge 00 |
      | Pledge 01 |
