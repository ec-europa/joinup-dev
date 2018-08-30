@api
Feature:
  In order to make it easy to browse to specific content
  As a moderator
  I need to be able to configure community content listing

  Background:
    Given the following collections:
      | title      | logo     | banner     | state     |
      | Nintendo64 | logo.png | banner.jpg | validated |
      | Emulators  | logo.png | banner.jpg | validated |
    And news content:
      | title                                 | collection | created           | content                          | state     |
      | Rare Nintendo64 disk drive discovered | Nintendo64 | 2018-10-01 4:26am | Magnetic drive called 64DD.      | validated |
      | NEC VR4300 CPU                        | Emulators  | 2018-10-03 4:27am | Update of the emulation library. | validated |
    And event content:
      | title               | collection | created           | body                                        | state     |
      | 20 year anniversary | Nintendo64 | 2018-10-01 4:29am | The console was released in September 1996. | validated |

  Scenario: Community content listing widget should be shown only to moderators.
    Given I am logged in as a facilitator of the "Nintendo64" collection
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    And the following fields should not be present "Display a community content listing, Include content shared in the collection, Query presets, Limit"

    Given I am logged in as a moderator
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    And the following fields should be present "Display a community content listing, Include content shared in the collection, Query presets, Limit"

  Scenario: Configure a custom page to show a community content listing.
    Given I am logged in as a moderator
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | Latest content                        |
      | Body  | Shows all content for this collection |
    And I check "Display a community content listing"
    And I press "Save"
    Then I should see the heading "Latest content"
    And I should see the "Rare Nintendo64 disk drive discovered" tile
    And I should see the "20 year anniversary" tile
    # Content from other collections should not be shown.
    But I should not see the "NEC VR4300 CPU" tile

    # Change the page to list only news.
    When I click "Edit" in the "Entity actions" region
    When I fill in the following:
      | Title | Latest news                        |
      | Body  | Shows all news for this collection |
    And I fill in "Query presets" with "entity_bundle|news"
    And I press "Save"
    Then I should see the heading "Latest news"
    And I should see the "Rare Nintendo64 disk drive discovered" tile
    But I should not see the "20 year anniversary" tile
    And I should not see the "NEC VR4300 CPU" tile

    When I click "Edit" in the "Entity actions" region
    And I check "Include content shared in the collection"
    And I press "Save"
    # Only news are displayed.
    Then I should see the "Rare Nintendo64 disk drive discovered" tile
    But I should not see the "20 year anniversary" tile
    And I should not see the "NEC VR4300 CPU" tile

    # Share a news inside the collection.
    Given I am logged in as a facilitator of the "Nintendo64" collection
    When I go to the "NEC VR4300 CPU" news
    And I click "Share"
    And I check "Nintendo64"
    And I press "Share"
    Then I should see the success message "Item was shared in the following collections: Nintendo64"

    When I go to the "Latest news" custom page
    Then I should see the "Rare Nintendo64 disk drive discovered" tile
    And I should see the "NEC VR4300 CPU" tile
    But I should not see the "20 year anniversary" tile

    # The news is removed from the list as soon as it's removed from sharing.
    When I go to the homepage of the "Nintendo64" collection
    And I click the contextual link "Unshare" in the "NEC VR4300 CPU" tile
    And I check "Nintendo64"
    And I press "Submit"
    Then I should see the success message "Item was unshared from the following collections: Nintendo64"

    When I go to the "Latest news" custom page
    Then I should see the "Rare Nintendo64 disk drive discovered" tile
    But I should not see the "20 year anniversary" tile
    And I should not see the "NEC VR4300 CPU" tile

    Given I am logged in as a moderator
    When I go to the "Latest news" custom page
    And I click "Edit" in the "Entity actions" region
    And I uncheck "Display a community content listing"
    And I press "Save"
    Then I should not see the "Rare Nintendo64 disk drive discovered" tile
    And I should not see the "20 year anniversary" tile
    And I should not see the "NEC VR4300 CPU" tile

  Scenario: Content type tabs should be mutually exclusive and show only items with results.
    Given I am logged in as a moderator
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | Collection content                        |
      | Body  | Shows all the content for this collection |
    And I check "Display a community content listing"
    And I press "Save"
    Then I should see the heading "Collection content"
    # Verify that unwanted facets are not shown in the page.
    # Assertion of the existing ones will be done through clicks in the
    # interface.
    And I should not see the following facet items "asset distribution, asset release, collection, contact information, custom page, licence, owner, solution"
    And I should see the following tiles in the correct order:
      | 20 year anniversary                   |
      | Rare Nintendo64 disk drive discovered |
    # Filter on news.
    When I click the News content tab
    Then I should see the "Rare Nintendo64 disk drive discovered" tile
    And I should not see the heading "20 year anniversary"
    # Some unwanted facets were showing after selecting one of the tabs.
    And I should not see the following facet items "asset distribution, asset release, collection, contact information, custom page, licence, owner, solution"
    # Filter on events.
    When I click the Event content tab
    Then I should see the heading "20 year anniversary"
    Then I should not see the heading "Rare Nintendo64 disk drive discovered"
