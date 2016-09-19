@api
Feature:
  In order to make it easy to browse to specific content
  As a moderator
  I need to be able to configure search options in a custom page

  Background:
    Given the following collections:
      | title      | logo     | banner     |
      | Nintendo64 | logo.png | banner.jpg |
      | Emulators  | logo.png | banner.jpg |
    And discussion content:
      | title               | collection | content                                     |
      | 20 year anniversary | Nintendo64 | The console was released in September 1996. |
      | NEC VR4300 CPU      | Emulators  | Designed by MTI for embedded applications.  |
    And news content:
      | title                                 | collection | body                        |
      | Rare Nintendo64 disk drive discovered | Nintendo64 | Magnetic drive called 64DD. |
    # Non UATable step.
    And I commit the solr index

    Scenario: Search field widget should be shown only to moderators
      Given I am logged in as a facilitator of the "Nintendo64" collection
      When I go to the homepage of the "Nintendo64" collection
      And I click "Add custom page"
      Then I should see the heading "Add custom page"
      And the following fields should not be present "Enable the search field, Query presets, Limit"

      Given I am logged in as a moderator
      When I go to the homepage of the "Nintendo64" collection
      And I click "Add custom page"
      Then I should see the heading "Add custom page"
      And the following fields should be present "Enable the search field, Query presets, Limit"

    Scenario: Configure a custom page to show only discussions of its collection
      Given I am logged in as a moderator
      When I go to the homepage of the "Nintendo64" collection
      And I click "Add custom page"
      Then I should see the heading "Add custom page"
      When I fill in the following:
        | Title                   | Discussions                               |
        | Body                    | Shows all discussions for this collection |
      And I check "Enable the search field"
      And I fill in "Query presets" with "aggregated_field|discussion"
      And I press "Save"
      Then I should see the heading "Discussions"
      And I should see the "20 year anniversary" tile
      # I should not see content that is not a discussion.
      And I should not see the text "Rare Nintendo64 disk drive discovered"
      # I should not see the discussions of another collection.
      But I should not see the text "NEC VR4300 CPU"
