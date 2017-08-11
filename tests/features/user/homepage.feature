@api @terms
Feature: Homepage feature
  As a registered user of the website
  when I visit the homepage of Joinup
  I want to see updates regarding the content that might be of interest to me.

  Scenario: Show content related to groups the user belongs to on the homepage.
    Given users:
      | Username     | Roles     | E-mail                   |
      | Henry Austin | Moderator | mod.murielle@example.com |
    And the following owner:
      | name        |
      | Jared Mcgee |
    And the following collections:
      | title               | description         | logo     | banner     | owner       | state     |
      | The Sacred Future   | The Sacred Future   | logo.png | banner.jpg | Jared Mcgee | validated |
      | Boy of Courage      | Boy of Courage      | logo.png | banner.jpg | Jared Mcgee | validated |
      | Legion Constitution | Legion Constitution | logo.png | banner.jpg | Jared Mcgee | validated |
    And news content:
      | title                     | body                      | policy domain     | collection          | state     | visits |
      | The Danger of the Bridges | The Danger of the Bridges | Finance in EU     | The Sacred Future   | validated | 649    |
      | Girl in the Dreams        | Girl in the Dreams        | Supplier exchange | Boy of Courage      | validated | 9421   |
      | An Explosion in Space     | An Explosion in Space     | E-health          | Legion Constitution | validated | 5064   |
      | Lightning Lass' Powers    | Lightning Lass' Powers    | Demography        | Legion Constitution | validated | 2951   |
    And the following collection user memberships:
      | collection        | user         | roles |
      | The Sacred Future | Henry Austin |       |

    When I am logged in as "Henry Austin"
    And I am on the homepage
    Then I should see the "The Danger of the Bridges" tile
    # Only content of collections I am a member of are shown.
    But I should not see the "Girl in the Dreams" tile

    # Show new list of content when I join a collection.
    When I go to the homepage of the "Boy of Courage" collection
    And I press the "Join this collection" button
    # Navigate to the homepage.
    When I am on the homepage
    Then I should see the "The Danger of the Bridges" tile
    And I should see the "Girl in the Dreams" tile

    # Show new list of content when I leave a collection.
    When I go to the homepage of the "Boy of Courage" collection
    And I click "Leave this collection"
    And I press the "Confirm" button
    # Navigate to the homepage.
    When I am on the homepage
    Then I should see the "The Danger of the Bridges" tile
    But I should not see the "Girl in the Dreams" tile

    # Show new items created within the parent.
    When I am logged in as a facilitator of the "The Sacred Future" collection
    And I go to the homepage of the "The Sacred Future" collection
    And I click "Add document" in the plus button menu
    And the following fields should not be present "Groups audience, Other groups, Create new revision, Revision log message, Shared in"
    And I fill in the following:
      | Title       | The Sacred Future documentation |
      | Short title | The Sacred Future documentation |
    And I enter "The Sacred Future documentation." in the "Description" wysiwyg editor
    And I select "Document" from "Type"
    Then I upload the file "test.zip" to "Upload a new file or enter a URL"
    And I press "Publish"
    Then I should see the heading "The Sacred Future documentation"

    When I am logged in as "Henry Austin"
    And I am on the homepage
    Then I should see the "The Sacred Future documentation" tile

    # An anonymous user should see the most popular content.
    Given I am not logged in
    And I am on the homepage
    Then I should see the following tiles in the correct order:
      | Girl in the Dreams              |
      | An Explosion in Space           |
      | Lightning Lass' Powers          |
      | The Danger of the Bridges       |
      | The Sacred Future documentation |
