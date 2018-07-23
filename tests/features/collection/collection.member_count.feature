@api
Feature: Collection homepage
  In order to have the users properly informed
  As an owner of the website
  I need to be able see the amount of users that are members of a collection

  Scenario: Membership counters are synced between the header and the members page.
    Given users:
      | Username | Status |
      | Thorin   | 1      |
      | Fili     | 1      |
      | Kili     | 1      |
      | Bilbo    | 0      |
    Given the following owner:
      | name         |
      | Hobbit owner |
    And the following collection:
      | title       | Under the mountain |
      | description | Under the mountain |
      | owner       | Hobbit owner       |
      | logo        | logo.png           |
      | moderation  | yes                |
      | state       | validated          |
    And the following collection user memberships:
      | collection         | user   | roles       |
      | Under the mountain | Thorin | facilitator |
      | Under the mountain | Fili   |             |
      | Under the mountain | Kili   |             |
      | Under the mountain | Bilbo  |             |

    When I go to the homepage of the "Under the mountain" collection
    Then I see the text "3 Members" in the "Header" region
    When I am logged in as a user with the "authenticated" role
    And I go to the homepage of the "Under the mountain" collection
    And I press the "Join this collection" button
    And I go to the homepage of the "Under the mountain" collection
    Then I see the text "4 Members" in the "Header" region
    When I click "Members"
    Then the "Roles" field should contain the "- Any - (4)" option

    # Ensure that a values are invalidated.
    Given I am logged in as a moderator
    And I am on the homepage
    When I click "People"
    And I fill in "Name or email contains" with "Bilbo"
    And I press the "Filter" button
    Then I check "Bilbo"
    Then I select "Unblock the selected user(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Unblock the selected user(s) was applied to 1 item."
    When I am not logged in
    When I go to the homepage of the "Under the mountain" collection
    Then I should see the text "5 Members" in the "Header" region
    When I click "Members"
    Then the "Roles" field should contain the "- Any - (5)" option

    # Block the user to ensure again tha the counters are updated.
    Given I am logged in as a moderator
    And I am on the homepage
    When I click "People"
    And I fill in "Name or email contains" with "Bilbo"
    And I press the "Filter" button
    Then I check "Bilbo"
    Then I select "Block the selected user(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Block the selected user(s) was applied to 1 item."
    When I am not logged in
    When I go to the homepage of the "Under the mountain" collection
    Then I should see the text "4 Members" in the "Header" region
    When I click "Members"
    Then the "Roles" field should contain the "- Any - (4)" option
