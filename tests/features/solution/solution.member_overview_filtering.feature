@api
Feature: Filtering the member list
  In order to quickly find a particular member
  As a moderator
  I need to be able to filter the member list

  Background:
    Given users:
      | Username     | First name | Family name | Roles     |
      | sludge       | Slurry     | Mud         | moderator |
      | ledge        | Jack       | Cartwright  |           |
      | user13343    | Jack       | Edgar       |           |
      | jackolantern | Carter     | Edgar       |           |
      | rightone     | Wright     | Jackson     |           |
      | brighty      | Lavonne    | Atkins      |           |
      | scanner      | Pucky      | Muck        |           |
    And the following collection:
      | title | Coffee lovers |
      | state | validated     |
    And the following solution:
      | title       | Coffee grinders                      |
      | description | Grind more coffee, make more coffee. |
      | state       | validated                            |
      | collection  | Coffee lovers                        |
    And the following solution user memberships:
      | solution        | user         | roles       |
      | Coffee grinders | ledge        | owner       |
      | Coffee grinders | user13343    | facilitator |
      | Coffee grinders | jackolantern |             |
      | Coffee grinders | rightone     |             |
      | Coffee grinders | brighty      |             |

  Scenario Outline: Only moderators are allowed to filter users in the solution members page.
    Given I am logged in as "<user>"
    When I go to the homepage of the "Coffee grinders" solution
    And I click "Members" in the "Left sidebar"
    Then the following fields should not be present "Username, First name, Family name"

    Examples:
      | user         |
      | ledge        |
      | user13343    |
      | jackolantern |
      | scanner      |

  Scenario: Privileged members should be able to filter users in the solutions members page.
    Given I am logged in as "sludge"
    When I go to the homepage of the "Coffee grinders" solution
    And I click "Members" in the "Left sidebar"
    Then the following fields should be present "Username, First name, Family name"

    When I fill in "Username" with "right"
    And I press "Apply"
    Then I should see the link "Wright Jackson"
    And I should see the link "Lavonne Atkins"
    But I should not see the link "Jack Cartwright"

    When I clear the content of the field "Username"
    When I fill in "First name" with "Jack"
    And I press "Apply"
    Then I should see the link "Jack Cartwright"
    And I should see the link "Jack Edgar"
    But I should not see the link "Wright Jackson"
    And I should not see the link "Carter Edgar"

    When I clear the field "First name"
    And I fill in "Family name" with "Edg"
    And I press "Apply"
    Then I should see the link "Jack Edgar"
    And I should see the link "Carter Edgar"
    But I should not see the link "Jack Cartwright"

    When I fill in "Username" with "use"
    And I press "Apply"
    Then I should see the link "Jack Edgar"
    But I should not see the link "Carter Edgar"
