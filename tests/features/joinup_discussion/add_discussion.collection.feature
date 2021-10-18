@api @group-d
Feature: Discussions added to collections
  In order to manage discussions
  As a collection member
  I need to be able to add "Discussion" content through UI.

  Scenario: "Add discussion" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following collections:
      | title              | logo     | banner     | state     |
      | The Fallen History | logo.png | banner.jpg | validated |
      | White Sons         | logo.png | banner.jpg | validated |

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "The Fallen History" collection
    Then I should not see the link "Add discussion"

    When I am an anonymous user
    And I go to the homepage of the "The Fallen History" collection
    Then I should not see the link "Add discussion"

    When I am logged in as a member of the "The Fallen History" collection
    And I go to the homepage of the "The Fallen History" collection
    Then I should see the link "Add discussion"

    When I am logged in as a "facilitator" of the "The Fallen History" collection
    And I go to the homepage of the "The Fallen History" collection
    Then I should see the link "Add discussion"
    # I should not be able to add a discussion to a different collection
    When I go to the homepage of the "White Sons" collection
    Then I should not see the link "Add discussion"

    When I am logged in as a "moderator"
    And I go to the homepage of the "The Fallen History" collection
    Then I should see the link "Add discussion"

  @terms @uploadFiles:test.zip
  Scenario: Add discussion as a facilitator.
    Given user:
      | Username    | kesha1988                             |
      | First name  | Kesha                                 |
      | Family name | Pontecorvo                            |
      | E-mail      | kesha.pontecorvo@ec-europa.example.eu |
    And collections:
      | title                  | logo     | banner     | state     |
      | The World of the Waves | logo.png | banner.jpg | validated |
    And the following collection user membership:
      | collection             | user      | roles       |
      | The World of the Waves | kesha1988 | facilitator |
    And I am logged in as kesha1988

    When I go to the homepage of the "The World of the Waves" collection
    And I click "Add discussion" in the plus button menu
    Then I should see the heading "Add discussion"
    And the following fields should be present "Title, Content, Topic, Add a new file"
    # The entity is new, so the current workflow state should not be shown.
    And the following fields should not be present "Current workflow state, Motivation"
    And the following fields should not be present "Shared on"

    # Check required fields.
    And I attach the file "test.zip" to "Add a new file"
    And I press "Upload"
    And I press "Publish"
    Then I should see the following lines of text:
      | Title field is required.                       |
      | The Attachments field description is required. |
      | Content field is required.                     |

    When I fill in the following:
      | Title            | An amazing discussion                      |
      | Content          | This is going to be an amazing discussion. |
      | File description | The content of this file is mind blowing   |
    And I press "Publish"
    Then I should see the error message "Topic field is required."

    And I select "EU and European Policies" from "Topic"
    And I press "Publish"

    Then I should see the heading "An amazing discussion"

    # Verify that the author is visible on the page.
    And I should see the text "Kesha Pontecorvo"
    And I should see the success message "Discussion An amazing discussion has been created."
    And the "The World of the Waves" collection has a discussion titled "An amazing discussion"

    # Attachments should be visible.
    And I should see the text "Attachments"
    And I should see the link "The content of this file is mind blowing"
    And I should see the text "176 bytes"

    # Regression test: the workflow state should not be shown to the user.
    But I should not see the text "State" in the "Content" region
    And I should not see the text "Validated" in the "Content" region

    # Check that the tile for the discussion is visible on the collection page.
    When I go to the homepage of the "The World of the Waves" collection
    Then I should see the link "An amazing discussion"
    And I should not see the text "Kesha Pontecorvo" in the "An amazing discussion" tile
    # Initially there are 0 comments on the discussion.
    And I should see the text "0" in the "An amazing discussion" tile

    # Make sure that the page is cached, so that we can ascertain that adding a
    # comment will invalidate the cache.
    And the page should be cacheable
    When I reload the page
    Then the page should be cached

    # Create two new comments and check that the counter is incremented.
    Given comments:
      | message                | author    | parent                |
      | Product up and running | kesha1988 | An amazing discussion |
      | Smart contract         | kesha1988 | An amazing discussion |
    When I reload the page
    Then I should see the text "2" in the "An amazing discussion" tile
    And I should not see the text "0" in the "An amazing discussion" tile

    # Check that the page cache has been correctly invalidated, and a reload
    # will serve again from cache.
    And the page should not be cached
    When I reload the page
    Then the page should be cached

    # Check that an anonymous user can see the information.
    Given I am an anonymous user
    When I go to the "An amazing discussion" discussion
    Then I should see the following headings:
      | The World of the Waves |
      | An amazing discussion  |
    And I should see the following lines of text:
      | This is going to be an amazing discussion. |
      | Attachments                                |
      | 176 bytes                                  |
    And I should see the link "The content of this file is mind blowing"
