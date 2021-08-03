@api @group-a
Feature: Community homepage
  In order to have the users properly informed
  As an owner of the website
  I need to be able see the amount of users that are members of a community

  Background:
    Given users:
      | Username    | Status  |
      | Thorin      | active  |
      | Fili        | active  |
      | Kili        | active  |
      | Bilbo       | blocked |
      | Some goblin | active  |
    Given the following owner:
      | name         |
      | Hobbit owner |
    And the following communities:
      | title                                   | description        | owner        | logo     | moderation | closed | state     |
      | Under the mountain                      | Under the mountain | Hobbit owner | logo.png | yes        | no     | validated |
      | Exactly the same community, but closed | Under the mountain | Hobbit owner | logo.png | yes        | yes    | validated |
    And the following community user memberships:
      | community                               | user   | roles       |
      | Under the mountain                      | Thorin | facilitator |
      | Under the mountain                      | Fili   |             |
      | Under the mountain                      | Kili   |             |
      | Under the mountain                      | Bilbo  |             |
      | Exactly the same community, but closed | Thorin | facilitator |
      | Exactly the same community, but closed | Fili   |             |
      | Exactly the same community, but closed | Kili   |             |
      | Exactly the same community, but closed | Bilbo  |             |

  Scenario: Counters are properly synced for open communities.
    When I go to the homepage of the "Under the mountain" community
    Then I see the text "3 Members" in the "Header" region
    When I am logged in as "Some goblin"
    And I go to the homepage of the "Under the mountain" community
    And I press the "Join this community" button
    And I go to the homepage of the "Under the mountain" community
    Then I see the text "4 Members" in the "Header" region
    And I click "Members" in the "Left sidebar"
    Then the "Roles" field should contain the "- Any - (4)" option

    # Ensure that leaving a community, updates the counters.
    When I click "Leave this community"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Under the mountain."
    Then I see the text "3 Members" in the "Header" region
    And I click "Members" in the "Left sidebar"
    Then the "Roles" field should contain the "- Any - (3)" option

  Scenario: Counters are properly synced for closed communities.
    When I go to the homepage of the "Exactly the same community, but closed" community
    Then I see the text "3 Members" in the "Header" region
    When I am logged in as "Some goblin"
    And I go to the homepage of the "Exactly the same community, but closed" community
    And I press the "Join this community" button
    And I go to the homepage of the "Exactly the same community, but closed" community
    Then I see the text "3 Members" in the "Header" region
    And I click "Members" in the "Left sidebar"
    Then the "Roles" field should contain the "- Any - (3)" option

    # Approve the membership.
    Given I am logged in as "Thorin"
    And I am on the members page of "Exactly the same community, but closed"
    # Assert that the user does not see the default OG tab.
    Then I should not see the link "Group"
    And I check the box "Update the member Some goblin"
    Then I select "Approve the pending membership(s)" from "Action"
    And I press the "Apply to selected items" button

    When I am an anonymous user
    And I go to the homepage of the "Exactly the same community, but closed" community
    Then I see the text "4 Members" in the "Header" region
    When I click "Members" in the "Left sidebar"
    Then the "Roles" field should contain the "- Any - (4)" option
    Then I see the text "4 Members" in the "Header" region

  Scenario: Blocked users are not counted towards total member count.
    # Cache the community overview page.
    When I am not logged in
    When I visit the community overview
    Then I should see the text "3" in the "Under the mountain" tile

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
    When I go to the homepage of the "Under the mountain" community
    Then I should see the text "4 Members" in the "Header" region
    And I click "Members" in the "Left sidebar"
    Then the "Roles" field should contain the "- Any - (4)" option

    # Ensure that cache is invalidated properly for tiles in search api views.
    When I visit the community overview
    Then I should see the text "4" in the "Under the mountain" tile

    # Block the user to ensure again that the counters are updated.
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
    When I go to the homepage of the "Under the mountain" community
    Then I should see the text "3 Members" in the "Header" region
    And I click "Members" in the "Left sidebar"
    Then the "Roles" field should contain the "- Any - (3)" option

    # Ensure that cache is invalidated properly for tiles in search api views.
    When I visit the community overview
    Then I should see the text "3" in the "Under the mountain" tile
