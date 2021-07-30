@api @group-a
Feature: Group overview of pledges.

  Background:
    Given the following owners:
      | name    | type                  | state |
      | Owner 1 | Private Individual(s) | validated |
      | Owner 2 | Private Individual(s) | validated |
      | Owner 3 | Private Individual(s) | validated |
      | Owner 4 | Private Individual(s) | validated |
    And the following collection:
      | title | Pledge overview challenge |
      | state | validated                 |
    And the following solution:
      | title | Pledge overview solution |
      | state | validated                |
    And user:
      | Username    | Pledge Owner             |
      | First name  | Pledge                   |
      | Family name | Owner                    |
      | E-mail      | pledge_owner@example.com |

  Scenario Outline: Pledges are listed as tiles in the overview of the group.
    Given pledge content:
      | title    | description           | contribution type | owner   | <group>                 | author       | created          | state     |
      | Pledge 1 | We would like to help | Monetary          | Owner 1 | Pledge overview <group> | Pledge Owner | 2020-01-01 16:00 | validated |
      | Pledge 2 | We would like to help | Loan              | Owner 2 | Pledge overview <group> | Pledge Owner | 2020-01-02 17:00 | validated |
      | Pledge 3 | We would like to help | Resources         | Owner 3 | Pledge overview <group> | Pledge Owner | 2020-01-03 18:00 | validated |
      | Pledge 4 | We would like to help | Other             | Owner 4 | Pledge overview <group> | Pledge Owner | 2020-01-04 19:00 | validated |

    Given I am not logged in
    When I go to the "Pledge overview <group>" <group>
    Then I should see the following tiles in the correct order:
      | Pledge 4 |
      | Pledge 3 |
      | Pledge 2 |
      | Pledge 1 |

    And I should see the text "Monetary" in the "Pledge 1" tile
    And I should see the text "Owner 1" in the "Pledge 1" tile
    And I should see the text "Loan" in the "Pledge 2" tile
    And I should see the text "Owner 2" in the "Pledge 2" tile
    And I should see the text "Resources" in the "Pledge 3" tile
    And I should see the text "Owner 3" in the "Pledge 3" tile
    And I should see the text "Other" in the "Pledge 4" tile
    And I should see the text "Owner 4" in the "Pledge 4" tile

    Examples:
      | group     |
      | challenge |
      | solution  |
