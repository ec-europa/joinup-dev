@api
Feature: Filtering the member list
  In order to quickly find a particular member
  As a moderator
  I need to be able to filter the member list

  Background:
    Given users:
      | Username   | First name | Family name | Roles     |
      | séamusline | Séamus     | Kingsbrooke | moderator |
      | emeritous  | King       | Seabrooke   |           |
      | user049230 | King       | Emerson     |           |
      | kingseamus | Seamus     | Emerson     |           |
      | brookebeau | Brooke     | Kingsley    |           |
      | iambroke   | Nell       | Gibb        |           |
      | queenson   | Queen      | Emerson     |           |
    And the following collection:
      | title       | Coffee makers                  |
      | description | Coffee is needed for survival. |
      | state       | validated                      |
    And the following collection user memberships:
      | collection    | user       | roles       |
      | Coffee makers | emeritous  | owner       |
      | Coffee makers | user049230 | facilitator |
      | Coffee makers | kingseamus |             |
      | Coffee makers | brookebeau |             |
      | Coffee makers | iambroke   |             |

  Scenario Outline: Only moderators are allowed to filter users in the collection members page.
    Given I am logged in as "<user>"
    When I go to the homepage of the "Coffee makers" collection
    And I click "Members" in the "Left sidebar"
    Then the following fields should not be present "Username, First name, Family name"

    Examples:
      | user       |
      | emeritous  |
      | user049230 |
      | kingseamus |
      | queenson   |

  Scenario: Moderators should be able to filter users in the collection members page.
    When I am logged in as "séamusline"
    And I go to the homepage of the "Coffee makers" collection
    And I click "Members" in the "Left sidebar"
    Then the following fields should be present "Username, First name, Family name"

    When I fill in "Username" with "bro"
    And I press "Apply"
    Then I should see the link "Brooke Kingsley"
    And I should see the link "Nell Gibb"
    But I should not see the link "King Seabrooke"

    When I clear the content of the field "Username"
    When I fill in "First name" with "King"
    And I press "Apply"
    Then I should see the link "King Seabrooke"
    And I should see the link "King Emerson"
    But I should not see the link "Brooke Kingsley"
    And I should not see the link "Seamus Emerson"

    When I clear the field "First name"
    And I fill in "Family name" with "eme"
    And I press "Apply"
    Then I should see the link "King Emerson"
    And I should see the link "Seamus Emerson"
    But I should not see the link "King Seabrooke"

    When I fill in "Username" with "use"
    And I press "Apply"
    Then I should see the link "King Emerson"
    But I should not see the link "Seamus Emerson"
