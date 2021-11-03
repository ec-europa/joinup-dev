@api @group-f
Feature: Type something to filter the listing the member list
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
    When I go to the members page of "Coffee grinders"
    But the following fields should be present "Type something to filter the list, Roles"

    Examples:
      | user         |
      | sludge       |
      | ledge        |
      | user13343    |
      | jackolantern |
      | scanner      |

  Scenario: Privileged members should be able to filter users in the solutions members page.
    Given I am logged in as "sludge"
    When I go to the members page of "Coffee grinders"
    And I fill in "Type something to filter the list" with "right"
    And I press "Apply"
    And I should see the link "Jack Cartwright"
    And I should see the link "Wright Jackson"
    # Matches by username should not be shown - regression check.
    But I should not see the link "Lavonne Atkins"

    When I clear the content of the field "Type something to filter the list"
    And I press "Apply"
    And I select "Owner (1)" from "Roles"
    And I press "Apply"
    Then I should see the link "Jack Cartwright"
    But I should not see the link "Jack Edgar"
    And I should not see the link "Carter Edgar"
    And I should not see the link "Wright Jackson"
    And I should not see the link "Lavonne Atkins"

    When I select "Facilitator (2)" from "Roles"
    And I press "Apply"
    Then I should see the link "Jack Edgar"
    And I should see the link "Jack Cartwright"
    But I should not see the link "Carter Edgar"
    And I should not see the link "Wright Jackson"
    And I should not see the link "Lavonne Atkins"
    And I fill in "Type something to filter the list" with "edg"
    And I press "Apply"
    Then I should see the link "Jack Edgar"
    And the option with text "Facilitator (1)" from select "Roles" is selected

    # Ensure that combined filters change the numbers in the "Roles" selection box.
    When I fill in "Type something to filter the list" with "Cartwright"
    And I press "Apply"
    Then I should see the link "Jack Cartwright"
    But I should not see the link "Jack Edgar"
    And the option with text "Facilitator (1)" from select "Roles" is selected

    # Ensure that filtering is based in all words.
    When I fill in "Type something to filter the list" with "Jack Edgar"
    And I press "Apply"
    Then I should see the link "Jack Edgar"
    But I should not see the link "Jack Cartwright"
    And I should not see the link "Carter Edgar"
    And the option with text "Facilitator (1)" from select "Roles" is selected
