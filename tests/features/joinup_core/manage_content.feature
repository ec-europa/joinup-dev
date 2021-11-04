@api @group-e
Feature:
  As a moderator
  In order to better organize the content
  I need to be able to move content between groups

  Scenario: Use the content management UI

    Given the following collections:
      | title        | state     |
      | Vintage Art  | validated |
      | Decadent Art | validated |
    And document content:
      | title             | document type | collection  | state     |
      | The Panama Papers | Document      | Vintage Art | validated |
      | The Area 51 File  | Document      | Vintage Art | draft     |
    And discussion content:
      | title               | collection  | state     |
      | The Ultimate Debate | Vintage Art | validated |
    And event content:
      | title                    | collection  | state     |
      | Version 2.0 Launch Party | Vintage Art | validated |
    And news content:
      | title                              | collection  | state     |
      | Exports Leap Despite Currency Gain | Vintage Art | validated |
    And custom_page content:
      | title                | collection  |
      | HOWTOs               | Vintage Art |
      | Looking for Support? | Vintage Art |
    And the following custom page menu structure:
      | title                | parent | weight |
      | Looking for Support? | HOWTOs | 1      |

    Given I am an anonymous user
    And I go to the homepage of the "Vintage Art" collection
    Then I should not see the link "Manage content"

    Given I am logged in as an "authenticated user"
    And I go to the homepage of the "Vintage Art" collection
    Then I should not see the link "Manage content"

    Given I am logged in as a facilitator of the "Vintage Art" collection
    And I go to the homepage of the "Vintage Art" collection
    Then I should not see the link "Manage content"

    Given I am logged in as a moderator

    # Visit one of the pages to warm up the render caches. We can then later
    # check that no stale cached content is being shown.
    When I go to the homepage of the "Vintage Art" collection
    And I click "HOWTOs" in the "Navigation menu block" region
    Then I should see the heading "Vintage Art"

    # Start the actual test scenario as a moderator.
    When I go to the homepage of the "Vintage Art" collection
    Then I should see the link "Manage content"
    Given I click "Manage content"
    Then I should see "Manage content" in the Header

    # Download the list as CSV.
    When I click "Download CSV"
    Then the response should contain "Type;Title;\"Created on\";\"Last update\";Published;URL"
    And the response should contain "Document;\"The Panama Papers\";"
    And the response should contain "/document/panama-papers"
    And the response should contain "Document;\"The Area 51 File\";"
    And the response should contain "/document/area-51-file"
    And the response should contain "Discussion;\"The Ultimate Debate\";"
    And the response should contain "/discussion/ultimate-debate"
    And the response should contain "Event;\"Version 2.0 Launch Party\";"
    And the response should contain "/event/version-20-launch-party"
    And the response should contain "News;\"Exports Leap Despite Currency Gain\";"
    And the response should contain "/news/exports-leap-despite-currency-gain"
    And the response should contain "\"Custom page\";HOWTOs;"
    And the response should contain "/collection/vintage-art/howtos"
    And the response should contain "\"Custom page\";\"Looking for Support?\";"
    And the response should contain "/collection/vintage-art/looking-support"

    When I go to the homepage of the "Vintage Art" collection
    Then I should see the link "Manage content"
    Given I click "Manage content"
    Then I should see "Manage content" in the Header

    # Select rows.
    Given I select the "The Panama Papers" row
    And I select the "The Ultimate Debate" row
    And I select the "Version 2.0 Launch Party" row
    And I select the "Exports Leap Despite Currency Gain" row
    And I select the "HOWTOs" row

    # Select the action.
    Given I select "Move to other group" from "Action"
    And I press "Apply to selected items"

    And I fill in "Select the destination collection or solution" with "Decadent Art"

    # Run the batch process.
    When I press "Apply"
    And I wait for the batch process to finish

    Then I should see "Document The Panama Papers group was changed to Decadent Art."
    And the "Decadent Art" collection should have a community content titled "The Panama Papers"

    Then I should see "News Exports Leap Despite Currency Gain group was changed to Decadent Art."
    And the "Decadent Art" collection should have a community content titled "Exports Leap Despite Currency Gain"

    And I should see "Discussion The Ultimate Debate group was changed to Decadent Art."
    And the "Decadent Art" collection should have a community content titled "The Ultimate Debate"

    And I should see "Event Version 2.0 Launch Party group was changed to Decadent Art."
    And the "Decadent Art" collection should have a community content titled "Version 2.0 Launch Party"

    And I should see "Custom page HOWTOs group was changed to Decadent Art."
    And the "Decadent Art" collection should have a custom page titled "HOWTOs"

    # The child page was moved too, even only its parent has been selected.
    And I should see "Child Custom page Looking for Support? group was changed too."
    And the "Decadent Art" collection should have a custom page titled "Looking for Support?"

    And I should see "Action processing results: Move to other group (5)."

    # Visit the source collection.
    Given I go to the "Vintage Art" collection
    And I should not see the "The Panama Papers" tile
    And I should not see the "Exports Leap Despite Currency Gain" tile
    And I should not see the "The Ultimate Debate" tile
    And I should not see the "Version 2.0 Launch Party" tile
    And I should not see the link "HOWTOs" in the "Navigation menu block" region
    And I should not see the link "Looking for Support?" in the "Navigation menu block" region

    # Visit the destination collection.
    Given I go to the "Decadent Art" collection
    And I should see the "The Panama Papers" tile
    And I should see the "Exports Leap Despite Currency Gain" tile
    And I should see the "The Ultimate Debate" tile
    And I should see the "Version 2.0 Launch Party" tile
    Then I should see the link "HOWTOs" in the "Navigation menu block" region
    When I click "HOWTOs" in the "Navigation menu block" region
    Then I should see the link "HOWTOs" in the "Navigation menu block" region
    And I should see the heading "Decadent Art"
    And I should not see the link "Looking for Support?" in the "Navigation menu block" region

    # Try moving a child page without moving the parent page. This should move
    # the child to the root of the menu.
    When I go to the homepage of the "Decadent Art" collection
    And I click "Manage content"
    And I select the "Looking for Support?" row
    And I select "Move to other group" from "Action"
    And I press "Apply to selected items"
    And I fill in "Select the destination collection or solution" with "Vintage Art"
    And I press "Apply"
    And I wait for the batch process to finish
    Then I should see "Custom page Looking for Support? group was changed to Vintage Art."
    And I should see "Action processing results: Move to other group (1)."
    And the "Vintage Art" collection should have a custom page titled "Looking for Support?"

    Given I go to the "Vintage Art" collection
    Then I should see the link "Looking for Support?" in the "Navigation menu block" region
    When I click "Looking for Support?" in the "Navigation menu block" region
    Then I should not see the "Looking for Support?" tile
