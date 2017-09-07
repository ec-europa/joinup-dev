@api @email
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
    Given users:
      | Username      | First name | Family name | E-mail               |
      | batbull       | Simba      | Hobson      | simba3000@hotmail.de |
      | welshbuzzard  | Titus      | Nicotera    | nicotito@example.org |
      | hatchingegg   | Korinna    | Morin       | korimor@example.com  |
    And the following collections:
      | title             | description        | state     | moderation |
      | Rumble collection | Sample description | validated | yes        |
    And "event" content:
      | title           | collection        | state     |
      | Seventh Windows | Rumble collection | validated |
    And "news" content:
      | title            | collection        | state     | author       |
      | The Playful Tale | Rumble collection | validated | batbull      |
      | Night of Shadow  | Rumble collection | proposed  | welshbuzzard |
    And "document" content:
      | title             | collection        | state     |
      | History of Flight | Rumble collection | validated |
    And "discussion" content:
      | title            | collection        | state     | author      |
      | The Men's Female | Rumble collection | validated | hatchingegg |

    # Check that visiting as a moderator does not create cache for all users.
    When I am logged in as a user with the "moderator" role
    And I am on the homepage
    And I click "Events, discussions, news ..."
    Then I should see the "Seventh Windows" tile
    And I should see the "The Playful Tale" tile
    And I should see the "History of Flight" tile
    And I should see the "The Men's Female" tile
    And I should not see the "Rumble collection" tile
    And I should not see the "Night of Shadow" tile

    # The tiles for discussion and news entities should show the full name of
    # the author instead of the username.
    And I should see the text "Simba Hobson" in the "The Playful Tale" tile
    And I should see the text "Korinna Morin" in the "The Men's Female" tile

    # Check page for authenticated users.
    When I am logged in as a user with the "authenticated" role
    And I am on the homepage
    And I click "Events, discussions, news ..."
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
