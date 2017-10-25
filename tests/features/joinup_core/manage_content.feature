@api
Feature:
  As a moderator
  In order to better organize the content
  I need to be able to move content between groups

  Scenario: Use the content management UI

    Given the following collections:
      | title                        | state     |
      | Grand Vintage Art Collection | validated |
      | Decadent Art Collection      | validated |
    And document content:
      | title             | document type | collection                   |
      | The Panama Papers | Document      | Grand Vintage Art Collection |
    And discussion content:
      | title               | collection                   |
      | The Ultimate Debate | Grand Vintage Art Collection |
    And event content:
      | title                    | collection                   |
      | Version 2.0 Launch Party | Grand Vintage Art Collection |
    And news content:
      | title                              | collection                   |
      | Exports Leap Despite Currency Gain | Grand Vintage Art Collection |
    And custom pages:
      | title                | collection                   | parent |
      | HOWTOs               | Grand Vintage Art Collection |        |
      | Looking for Support? | Grand Vintage Art Collection | HOWTOs |

    Given I am an anonymous user
    And I go to the homepage of the "Grand Vintage Art Collection" collection
    Then I should not see the link "Manage content"

    Given I am logged in as an "authenticated user"
    And I go to the homepage of the "Grand Vintage Art Collection" collection
    Then I should not see the link "Manage content"

    Given I am logged in as a facilitator of the "Grand Vintage Art Collection" collection
    And I go to the homepage of the "Grand Vintage Art Collection" collection
    Then I should not see the link "Manage content"

    Given I am logged in as a moderator
    And I go to the homepage of the "Grand Vintage Art Collection" collection
    Then I should see the link "Manage content"

    Given I click "Manage content"
    Then I should see "Manage content" in the SevenHeader

    # Select rows.
    Given I select the "The Panama Papers" row
    And I select the "The Ultimate Debate" row
    And I select the "Version 2.0 Launch Party" row
    And I select the "Exports Leap Despite Currency Gain" row
    And I select the "HOWTOs" row

    # Select the action.
    Given I select "Move to other group" from "Action"
    And I press "Apply to selected items"

    And I fill in "Select the destination collection or solution" with "Decadent Art Collection"

    # Run the batch process.
    When I press "Apply"
    And I wait for the batch process to finish

    Then I should see "Document The Panama Papers group was changed to Decadent Art Collection."
    And the "Decadent Art Collection" collection should have a community content titled "The Panama Papers"

    Then I should see "News Exports Leap Despite Currency Gain group was changed to Decadent Art Collection."
    And the "Decadent Art Collection" collection should have a community content titled "Exports Leap Despite Currency Gain"

    And I should see "Discussion The Ultimate Debate group was changed to Decadent Art Collection."
    And the "Decadent Art Collection" collection should have a community content titled "The Ultimate Debate"

    And I should see "Event Version 2.0 Launch Party group was changed to Decadent Art Collection."
    And the "Decadent Art Collection" collection should have a community content titled "Version 2.0 Launch Party"

    And I should see "Custom page HOWTOs group was changed to Decadent Art Collection."
    And the "Decadent Art Collection" collection should have a custom page titled "HOWTOs"

    # The child page was moved too, even only its parent has been selected.
    And I should see "Child Custom page Looking for Support? group was changed too."
    And the "Decadent Art Collection" collection should have a custom page titled "Looking for Support?"

    And I should see "Action processing results: Move to other group (5)."
