@api
Feature:
  In order to make it easy to browse to specific content
  As a moderator
  I need to be able to configure community content listing

  Background:
    Given the following collections:
      | title      | logo     | banner     | state |
      | Nintendo64 | logo.png | banner.jpg | draft |
      | Emulators  | logo.png | banner.jpg | draft |
    And news content:
      | title                                 | collection | content                          |
      | Rare Nintendo64 disk drive discovered | Nintendo64 | Magnetic drive called 64DD.      |
      | NEC VR4300 CPU                        | Emulators  | Update of the emulation library. |
    And event content:
      | title               | collection | body                                        |
      | 20 year anniversary | Nintendo64 | The console was released in September 1996. |

    Scenario: Community content listing widget should be shown only to moderators
      Given I am logged in as a facilitator of the "Nintendo64" collection
      When I go to the homepage of the "Nintendo64" collection
      And I click "Add custom page"
      Then I should see the heading "Add custom page"
      And the following fields should not be present "Display a community content listing, Query presets, Limit"

      Given I am logged in as a moderator
      When I go to the homepage of the "Nintendo64" collection
      And I click "Add custom page"
      Then I should see the heading "Add custom page"
      And the following fields should be present "Display a community content listing, Query presets, Limit"

    Scenario: Configure a custom page to show only news of its collection
      Given I am logged in as a moderator
      When I go to the homepage of the "Nintendo64" collection
      And I click "Add custom page"
      Then I should see the heading "Add custom page"
      When I fill in the following:
        | Title | Latest news                        |
        | Body  | Shows all news for this collection |
      And I check "Display a community content listing"
      And I fill in "Query presets" with "entity_bundle|news"
      And I press "Save"
      Then I should see the heading "Latest news"
      And I should see the "Rare Nintendo64 disk drive discovered" tile
      # I should not see content that is not a discussion.
      And I should not see the text "20 year anniversary"
      # I should not see the discussions of another collection.
      But I should not see the text "NEC VR4300 CPU"

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
      And I should see the "Rare Nintendo64 disk drive discovered" tile
      # Events tile template is not yet in place. See #ISAICP-2723.
      And I should see the heading "20 year anniversary"
      # Filter on news.
      When I click the news content tab
      Then I should see the "Rare Nintendo64 disk drive discovered" tile
      And I should not see the heading "20 year anniversary"
      # Some unwanted facets were showing after selecting one of the tabs.
      And I should not see the following facet items "asset distribution, asset release, collection, contact information, custom page, licence, owner, solution"
      # Filter on events.
      When I click the event content tab
      Then I should see the heading "20 year anniversary"
      Then I should not see the heading "Rare Nintendo64 disk drive discovered"
