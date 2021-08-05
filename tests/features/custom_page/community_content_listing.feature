@api @group-b
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
    And discussion content:
      | title                           | collection | content                 | created          | state     |
      | What's your favourite N64 game? | Nintendo64 | Post title and reasons. | 2018-11-17 10:17 | validated |
      | Searching for green pad.        | Nintendo64 | Looking for a used one. | 2018-11-17 10:18 | validated |

  Scenario: Community content listing widget should be shown to facilitators and moderators.
    Given I am logged in as a facilitator of the "Nintendo64" collection
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    And the following fields should not be present "Show related content, Allow shared content"
    # The button appears in a non-javascript environment. The dropdown needs to be pressed in a javascript
    # enabled browser.
    And I should see the button "Add Content listing"
    # Fill in title so that CSS will not complain about empty field when pressing a button.
    When I fill in "Title" with "Hello world"
    And I press "Add Content listing"
    And the following fields should be present "Show related content, Allow shared content"
    And the following fields should not be present "Query presets, Limit"

    Given I am logged in as a moderator
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    And the following fields should not be present "Show related content, Allow shared content"
    # The button appears in a non-javascript environment. The dropdown needs to be pressed in a javascript
    # enabled browser.
    And I should see the button "Add Content listing"
    # Fill in title so that CSS will not complain about empty field when pressing a button.
    When I fill in "Title" with "Hello world"
    And I press "Add Content listing"
    And the following fields should be present "Show related content, Allow shared content, Query presets, Limit"
    And I should see the button "Add and configure filter"

  Scenario: Configure a custom page to show a community content listing.
    Given I am logged in as a facilitator of the "Nintendo64" collection
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | Latest content                        |
      | Body  | Shows all content for this collection |
    And I press "Add Content listing"
    And I select "News" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "News" with "Rare Nintendo64 disk drive discovered"
    And I select "Event" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "Event" with "20 year anniversary"

    Then I should see the text "Related content from the community will be displayed below the one that you are publishing."
    And I should see the text "Display content shared from other communities."
    And I should see the text "Note: the content shown is dynamic, filtered live each time users will visualise the page. As a result, new content might be shown and old content can be altered or deleted."

    When I press "Save"
    Then I should see the heading "Latest content"
    And I should see the "Rare Nintendo64 disk drive discovered" tile
    And I should see the "20 year anniversary" tile
    # Content from other collections should not be shown.
    But I should not see the "NEC VR4300 CPU" tile

    # Change the page to list only news.
    Given I am logged in as a moderator
    When I go to the "Latest content" custom page
    And I click "Edit" in the "Entity actions" region
    And I fill in the following:
      | Title | Latest news                        |
      | Body  | Shows all news for this collection |
    And I fill in "Query presets" with "entity_bundle|news"
    And I press "Save"
    Then I should see the heading "Latest news"
    And I should see the "Rare Nintendo64 disk drive discovered" tile
    But I should not see the "20 year anniversary" tile
    And I should not see the "NEC VR4300 CPU" tile

    Given I am logged in as a facilitator of the "Nintendo64" collection
    And I go to the "Latest news" custom page
    When I click "Edit" in the "Entity actions" region
    And I check "Allow shared content"
    And I press "Save"
    # Only news are displayed.
    Then I should see the "Rare Nintendo64 disk drive discovered" tile
    But I should not see the "20 year anniversary" tile
    And I should not see the "NEC VR4300 CPU" tile

    # Share a news inside the collection.
    When I go to the "NEC VR4300 CPU" news
    And I click "Share"
    And I check "Nintendo64"
    And I press "Share"
    Then I should see the success message "Item was shared on the following groups: Nintendo64"

    When I go to the "Latest news" custom page
    Then I should see the "Rare Nintendo64 disk drive discovered" tile
    And I should not see the "NEC VR4300 CPU" tile
    But I should not see the "20 year anniversary" tile

    # The news is removed from the list as soon as it's removed from sharing.
    When I go to the homepage of the "Nintendo64" collection
    And I click the contextual link "Unshare" in the "NEC VR4300 CPU" tile
    And I check "Nintendo64"
    And I press "Submit"
    Then I should see the success message "Item was unshared from the following groups: Nintendo64"

    When I go to the "Latest news" custom page
    Then I should see the "Rare Nintendo64 disk drive discovered" tile
    But I should not see the "20 year anniversary" tile
    And I should not see the "NEC VR4300 CPU" tile

    When I click "Edit" in the "Entity actions" region
    And I uncheck "Show related content"
    And I press "Save"
    Then I should not see the "Rare Nintendo64 disk drive discovered" tile
    And I should not see the "20 year anniversary" tile
    And I should not see the "NEC VR4300 CPU" tile

  @javascript
  Scenario: Configure a custom page to show specific tiles.
    Given I am logged in as a facilitator of the "Nintendo64" collection
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page" in the plus button menu
    Then I should see the heading "Add custom page"
    When I fill in "Title" with "Chosen content"
    And I enter "Shows a specific set of tiles." in the "Body" wysiwyg editor

    # The "List additional actions" is the button arrow that shows the full list of paragraphs to add.
    Given I press "List additional actions"
    And I press "Add Content listing"
    And I wait for AJAX to finish
    And I select "Discussion" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "Discussion" with "Searching for green pad."
    And I select "Discussion" from "Available filters"
    And I press "Add and configure filter"
    And I fill in the 2nd "Discussion" with "What's your favourite N64 game?" in the "Custom content listing" field
    And I select "News" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "News" with "Rare Nintendo64 disk drive discovered"
    And I select "Event" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "Event" with "20 year anniversary"
    Then I drag the table row in the "Content listing field filter form" region at position 4 up
    And I press "Save"
    Then I should see the heading "Chosen content"
    And I should see the following tiles in the correct order:
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | 20 year anniversary                   |
      | Rare Nintendo64 disk drive discovered |

    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I drag the table row in the "Content listing field filter form" region at position 3 up
    And I drag the table row in the "Content listing field filter form" region at position 2 up
    And I press "Save"
    And I should see the following tiles in the correct order:
      | 20 year anniversary                   |
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | Rare Nintendo64 disk drive discovered |

    # Content that doesn't belong to the collection won't show up, even when selected.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I select "News" from "Available filters"
    And I press "Add and configure filter"
    And I fill in the 2nd "News" with "NEC VR4300 CPU" in the "Custom content listing" field
    And I check "Allow shared content"
    And I press "Save"
    Then I should see the following tiles in the correct order:
      | 20 year anniversary                   |
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | Rare Nintendo64 disk drive discovered |

    # Content shared on the collection will be shown.
    When I go to the "NEC VR4300 CPU" news
    And I click "Share"
    And I check "Nintendo64"
    And I press "Share" in the "Modal buttons" region
    Then I should see the success message "Item was shared on the following groups: Nintendo64"
    When I go to the "Chosen content" custom page
    Then I should see the following tiles in the correct order:
      | 20 year anniversary                   |
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | Rare Nintendo64 disk drive discovered |
      | NEC VR4300 CPU                        |

    # Disabling inclusion of shared content will remove it from the list, even if still referenced.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I uncheck "Allow shared content"
    And I press "Save"
    Then I should see the following tiles in the correct order:
      | 20 year anniversary                   |
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | Rare Nintendo64 disk drive discovered |

    # Create a solution and add it to the list.
    Given the following solution:
      | title      | N64 cartridge cleaner |
      | state      | validated             |
      | collection | Nintendo64            |
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    # Simulate reordering and removal of rows.
    And I drag the table row in the "Content listing field filter form" region at position 4 up
    And I drag the table row in the "Content listing field filter form" region at position 3 up
    And I drag the table row in the "Content listing field filter form" region at position 1 down
    # Remove the first two rows.
    And I press "Remove filter"
    And I wait for AJAX to finish
    And I press "Remove filter"
    And I wait for AJAX to finish
    And I select "Solution" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "Solution" with "N64 cartridge cleaner"
    And I press "Save"
    Then I should see the following tiles in the correct order:
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | N64 cartridge cleaner                 |

    # Query presets should still apply when available.
    When I am logged in as a moderator
    And I go to the "Chosen content" custom page
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I fill in "Query presets" with "entity_bundle|solution"
    And I press "Save"
    Then I should see the following tiles in the correct order:
      | N64 cartridge cleaner |

    # When a query preset is entered, the query builder should not be available
    # anymore to facilitators.
    Given I am logged in as a facilitator of the "Nintendo64" collection
    And I go to the "Chosen content" custom page
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then the following field should not be present "Available filters"

    # Moderators should still have access to the query builder.
    When I am logged in as a moderator
    And I go to the "Chosen content" custom page
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then the following field should be present "Available filters"

  Scenario: Content type tabs should be mutually exclusive and show only items with results.
    Given I am logged in as a facilitator of the "Nintendo64" collection
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | Collection content                        |
      | Body  | Shows all the content for this collection |
    And I press "Add Content listing"
    And I select "Discussion" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "Discussion" with "Searching for green pad."
    And I select "Discussion" from "Available filters"
    And I press "Add and configure filter"
    And I fill in the 2nd "Discussion" with "What's your favourite N64 game?" in the "Custom content listing" field
    And I select "Event" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "Event" with "20 year anniversary"
    And I select "News" from "Available filters"
    And I press "Add and configure filter"
    And I fill in "News" with "Rare Nintendo64 disk drive discovered"
    And I press "Save"
    Then I should see the heading "Collection content"
    # Verify that unwanted facets are not shown in the page.
    And I should see the following facet items "Discussion, Event, News" in this order
    And I should see the following tiles in the correct order:
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | 20 year anniversary                   |
      | Rare Nintendo64 disk drive discovered |
    # Filter on news.
    When I click the News content tab
    Then I should see the "Rare Nintendo64 disk drive discovered" tile
    And I should not see the heading "20 year anniversary"
    # Some unwanted facets were showing after selecting one of the tabs.
    And I should see the following facet items "News, Discussion, Event" in this order
    # Filter on events.
    When I click the Event content tab
    Then I should see the heading "20 year anniversary"
    Then I should not see the heading "Rare Nintendo64 disk drive discovered"

  Scenario: Test listing by keywords that contain the same word.
    Given document content:
      | title        | keywords            | content     | collection | state     |
      | User's Guide | nintendo64 manuals  | User manual | Nintendo64 | validated |
      | Licence      | nintendo64 licences | Licence     | Nintendo64 | validated |
    And I am logged in as a moderator

    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"

    When I fill in the following:
      | Title | Manuals        |
      | Body  | Product guides |
    And I press "Add Content listing"
    And I fill in "Query presets" with:
        """
        entity_bundle|document
        keywords|"nintendo64 manuals"
        """
    When I press "Save"

    Then I should see the "User's Guide" tile
    But I should not see the "Licence" tile

    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"

    When I fill in the following:
      | Title | Licences          |
      | Body  | Product licensing |
    And I press "Add Content listing"
    And I fill in "Query presets" with:
        """
        entity_bundle|document
        keywords|"nintendo64 licences"
        """
    When I press "Save"

    Then I should see the "Licence" tile
    But I should not see the "User's Guide" tile

  Scenario: Invalid entries in the query presets field show a validation error.
    Given I am logged in as a moderator
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    When I fill in "Title" with "Query presets validation"
    And I press "Add Content listing"
    And I fill in "Query presets" with "wrongvalue"
    And I press "Save"
    Then I should see the error message "Invalid query preset line added: wrongvalue."
    When I fill in "Query presets" with "unknown_field|news"
    And I press "Save"
    Then I should see the error message "Invalid search field specified: unknown_field."
    When I fill in "Query presets" with "entity_bundle|news|equal"
    And I press "Save"
    Then I should see the error message "Invalid operator specified: equal. Allowed operators are '=', '<>', 'IN', 'NOT IN'."
    # Verify that errors are reported when multiline values are added.
    When I fill in "Query presets" with:
        """
        entity_bundle|news
        unknown_field|test
        """
    And I press "Save"
    Then I should see the error message "Invalid search field specified: unknown_field."

  Scenario: Empty query presets and query builder field show a validation error.
    Given I am logged in as a moderator
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    When I fill in "Title" with "Empty queries"
    And I press "Add Content listing"
    And I press "Save"
    Then I should see the error message "You need to add a filter in the Content listing block"

  @terms
  Scenario: Global search setting allows for site-wide content in the content listing.
    Given I am logged in as a moderator
    When I go to the homepage of the "Nintendo64" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | All content     |
      | Body  | Show EVERYTHING |
    And I press "Add Content listing"
    And I fill in "Query presets" with:
        """
        entity_bundle|discussion,event,news|IN
        """
    And I check "Global search"
    And I press "Save"
    Then I should see the heading "All content"
    # All tiles are available.
    And I should see the following tiles in the correct order:
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | NEC VR4300 CPU                        |
      | 20 year anniversary                   |
      | Rare Nintendo64 disk drive discovered |

    # Ensure that when content is created or editted in another group,
    # the page cache is invalidated.
    Given I go to the homepage of the "Emulators" collection
    And I click "Add news" in the plus button menu
    When I fill in the following:
      | Short title | Current wars               |
      | Headline    | Edisson vs Electro         |
      | Content     | A new movie is coming out. |
    And I select "Statistics and Analysis" from "Topic"
    And I press "Publish"
    Then I should see the heading "Current wars"

    When I go to the homepage of the "Nintendo64" collection
    And I click "All content" in the "Left sidebar" region
    And I should see the following tiles in the correct order:
      | Current wars                          |
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | NEC VR4300 CPU                        |
      | 20 year anniversary                   |
      | Rare Nintendo64 disk drive discovered |

    When I go to the edit form of the "Current wars" news
    And I fill in "Short title" with "Current wars is over"
    And I press "Update"
    Then I should see the heading "Current wars is over"

    When I go to the homepage of the "Nintendo64" collection
    And I click "All content" in the "Left sidebar" region
    And I should see the following tiles in the correct order:
      | Current wars is over                  |
      | Searching for green pad.              |
      | What's your favourite N64 game?       |
      | NEC VR4300 CPU                        |
      | 20 year anniversary                   |
      | Rare Nintendo64 disk drive discovered |
