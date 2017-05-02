@api
Feature: Content Overview

  Scenario: Check visibility of "Content" menu link.
    Given I am an anonymous user
    Then I should see the link "Content"
    When I click "Content"
    Then I should see the heading "Content"
    # Check that all logged in users can see and access the link as well.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Content"
    When I click "Content"
    Then I should see the heading "Content"

  # @todo: The small header, which contains content link, should be removed for anonymous users on the homepage.
  # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2639.
  @terms
  Scenario: View content overview as an anonymous user
    Given the following collections:
      | title             | description        | state     | moderation |
      | Rumble collection | Sample description | validated | yes        |
    And "event" content:
      | title           | collection        | state     |
      | Seventh Windows | Rumble collection | validated |
    And "news" content:
      | title            | collection        | state     |
      | The Playful Tale | Rumble collection | validated |
      | Night of Shadow  | Rumble collection | proposed  |
    And "document" content:
      | title             | collection        | state     |
      | History of Flight | Rumble collection | validated |
    And "discussion" content:
      | title            | collection        | state     |
      | The Men's Female | Rumble collection | validated |

    # Check that visiting as a moderator does not create cache for all users.
    When I am logged in as a user with the "moderator" role
    And I am on the homepage
    And I click "Content"
    Then I should see the "Seventh Windows" tile
    And I should see the "The Playful Tale" tile
    And I should see the "History of Flight" tile
    And I should see the "The Men's Female" tile
    And I should not see the "Rumble collection" tile
    And I should see the "Night of Shadow" tile

    # Check page for authenticated users.
    When I am logged in as a user with the "authenticated" role
    And I am on the homepage
    And I click "Content"
    Then I should see the "Seventh Windows" tile
    And I should see the "The Playful Tale" tile
    And I should see the "History of Flight" tile
    And I should see the "The Men's Female" tile
    But I should not see the "Rumble collection" tile
    And I should not see the "Night of Shadow" tile

    # Check the page for anonymous users.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Content"
    When I click "Content"
    Then I should see the "Seventh Windows" tile
    And I should see the "The Playful Tale" tile
    And I should see the "History of Flight" tile
    And I should see the "The Men's Female" tile
    But I should not see the "Rumble collection" tile
    And I should not see the "Night of Shadow" tile
