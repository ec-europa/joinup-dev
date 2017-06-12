@api
Feature: Unpublished content of the website
  In order to manage unpublished entities
  As a user of the website
  I want to be able to find unpublished content that I can work on

  Background: Test visibility of unpublished data.
    Given users:
      | Username       | Roles |
      | Ed Abbott      |       |
      | Preston Fields |       |
      | Brenda Day     |       |
      | Phillip Shaw   |       |
    And the following collections:
      | title               | description         | state     | elibrary creation | moderation |
      | Invisible Boyfriend | Invisible Boyfriend | validated | members           | no         |
      | Grey Swords         | Invisible Boyfriend | proposed  | members           | no         |
      | Nothing of Slaves   | Invisible Boyfriend | draft     | members           | no         |
    And the following collection user memberships:
      | collection          | user           | roles         |
      | Invisible Boyfriend | Ed Abbott      | authenticated |
      | Invisible Boyfriend | Preston Fields | authenticated |
      | Invisible Boyfriend | Phillip Shaw   | facilitator   |
    And "event" content:
      | title                | author    | collection          | state     |
      | The Ragged Streams   | Ed Abbott | Invisible Boyfriend | proposed  |
      | Storms of Touch      | Ed Abbott | Invisible Boyfriend | validated |
      | The Male of the Gift | Ed Abbott | Invisible Boyfriend | validated |
      | Mists in the Thought | Ed Abbott | Invisible Boyfriend | draft     |

  Scenario Outline: Both the owner and the authenticated users should be able to see all content.
    When I am logged in as "<user>"
    And I go to the "Invisible Boyfriend" collection
    Then I should see the "The Ragged Streams" tile
    And I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    And I should see the "Mists in the Thought" tile

    Examples:
      | user         |
      | Ed Abbott    |
      | Phillip Shaw |

  Scenario Outline: Other members and authenticated users should only see the published items.
    When I am logged in as "<user>"
    And I go to the "Invisible Boyfriend" collection
    Then I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    But I should not see the "The Ragged Streams" tile
    And I should not see the "Mists in the Thought" tile

    Examples:
      | user           |
      | Preston Fields |
      | Brenda Day     |

  Scenario: The author should be able to see all his content in his profile.
    When I am logged in as "Ed Abbott"
    And I visit "/user"
    Then I should see the "The Ragged Streams" tile
    And I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    And I should see the "Mists in the Thought" tile

  Scenario: The moderator should see the proposed collections on his dashboard.
    When I am logged in as a moderator
    And I go to the dashboard
    Then I should see the "Grey Swords" tile
    But I should not see the "Invisible Boyfriend" tile
    And I should not see the "Nothing of Slaves" tile
