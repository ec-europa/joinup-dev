@api @group-f
Feature: Pinning content entities inside solutions
  As a facilitator of a solution
  I want to pin content at the top of the solution homepage
  So that important content has more visibility

  Background:
    Given the following solutions:
      | title       | state     |
      | Blue Wrench | validated |
      | Sunny Beam  | validated |
    And users:
      | Username          | E-mail                        |
      | Sara Jessica      | sara.jessica@example.com      |
      | Tyron Lannister   | tyron.lannister@example.com   |
      | Andy Garcia       | andy.garcia@example.com       |
      | Melahrini Gilbert | melahrini.gilbert@example.com |
    And the following solution user memberships:
      | solution    | user              | roles       |
      | Blue Wrench | Sara Jessica      | facilitator |
      | Sunny Beam  | Sara Jessica      | facilitator |
      | Blue Wrench | Tyron Lannister   |             |
      | Sunny Beam  | Andy Garcia       | facilitator |
      | Sunny Beam  | Melahrini Gilbert |             |

  Scenario Outline: Facilitators can pin and unpin community content inside their solutions.
    Given discussion content:
      | title                      | solution    | state     | created    |
      | What is the HEX for lemon? | Blue Wrench | validated | 2018-12-13 |
    And <content type> content:
      | title              | solution    | state     | pinned | created    |
      | Very important     | Blue Wrench | validated | yes    | 2018-11-03 |
      | Useful information | Blue Wrench | validated | no     | 2018-12-03 |

    When I am an anonymous user
    And I go to the homepage of the "Blue Wrench" solution
    # Todo: Due to an environment related issue on CPHP this is causing random
    #   failures which cannot be replicated in production. Re-enable this check
    #   once we have updated to a more recent version of Solr. See ISAICP-6245.
    # Then I should see the following tiles in the correct order:
    #   | Very important             |
    #   | What is the HEX for lemon? |
    #   | Useful information         |
    Then I should not see the contextual links "Pin, Unpin" in the "Useful information" tile
    And I should not see the contextual links "Pin, Unpin" in the "Very important" tile

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Blue Wrench" solution
    Then I should not see the contextual links "Pin, Unpin" in the "Useful information" tile
    And I should not see the contextual links "Pin, Unpin" in the "Very important" tile

    # Members and facilitators of other solutions cannot pin nor unpin.
    When I am logged in as "Andy Garcia"
    And I go to the homepage of the "Blue Wrench" solution
    Then I should not see the contextual links "Pin, Unpin" in the "Useful information" tile
    And I should not see the contextual links "Pin, Unpin" in the "Very important" tile
    When I am logged in as "Melahrini Gilbert"
    And I go to the homepage of the "Blue Wrench" solution
    Then I should not see the contextual links "Pin, Unpin" in the "Useful information" tile
    And I should not see the contextual links "Pin, Unpin" in the "Very important" tile

    # Collection members cannot pin nor unpin content.
    When I am logged in as "Tyron Lannister"
    And I go to the homepage of the "Blue Wrench" solution
    Then I should not see the contextual links "Pin, Unpin" in the "Useful information" tile
    And I should not see the contextual links "Pin, Unpin" in the "Very important" tile

    # Facilitators of the solution the content belongs to can pin/unpin.
    When I am logged in as "Sara Jessica"
    And I go to the homepage of the "Blue Wrench" solution
    Then I should see the contextual link "Pin" in the "Useful information" tile
    And I should see the contextual link "Unpin" in the "Very important" tile
    But I should not see the contextual link "Unpin" in the "Useful information" tile
    And I should not see the contextual link "Pin" in the "Very important" tile

    When I click the contextual link "Unpin" in the "Very important" tile
    Then I should see the success message "<label> Very important has been unpinned in the solution Blue Wrench."
    # Todo: Due to an environment related issue on CPHP this is causing random
    #   failures which cannot be replicated in production. Re-enable this check
    #   once we have updated to a more recent version of Solr. See ISAICP-6245.
    # And I should see the following tiles in the correct order:
    #   | What is the HEX for lemon? |
    #   | Useful information         |
    #   | Very important             |
    And I should see the contextual link "Pin" in the "Very important" tile
    But I should not see the contextual link "Unpin" in the "Very important" tile

    When I click the contextual link "Pin" in the "Useful information" tile
    Then I should see the success message "<label> Useful information has been pinned in the solution Blue Wrench."
    # Todo: Due to an environment related issue on CPHP this is causing random
    #   failures which cannot be replicated in production. Re-enable this check
    #   once we have updated to a more recent version of Solr. See ISAICP-6245.
    # And I should see the following tiles in the correct order:
    #   | Useful information         |
    #   | What is the HEX for lemon? |
    #   | Very important             |
    And I should see the contextual link "Unpin" in the "Useful information" tile
    But I should not see the contextual link "Pin" in the "Useful information" tile

    # When we are not in a solution page, the pin/unpin links should not be visible.
    When I am at "/search"
    Then I should not see the contextual links "Pin, Unpin" in the "Useful information" tile
    And I should not see the contextual links "Pin, Unpin" in the "Very important" tile

    Examples:
      | content type | label      |
      | event        | Event      |
      | document     | Document   |
      | discussion   | Discussion |
      | news         | News       |

  @javascript
  Scenario Outline: Pinned content tiles should show a visual cue only in their solution homepage.
    Given <content type> content:
      | title       | solution    | state     | pinned |
      | Lantern FAQ | Blue Wrench | validated | yes    |
      | Lantern TCA | Blue Wrench | validated | no     |

    When I go to the homepage of the "Blue Wrench" solution
    Then the "Lantern FAQ" tile should be marked as pinned
    But the "Lantern TCA" tile should not be marked as pinned

    When I am at "/search"
    Then the "Lantern FAQ" tile should not be marked as pinned

    # Verify that changes in the pinned state are reflected to the tile.
    When I am logged in as a facilitator of the "Blue Wrench" solution
    When I go to the homepage of the "Blue Wrench" solution
    Then the "Lantern FAQ" tile should be marked as pinned
    But the "Lantern TCA" tile should not be marked as pinned

    When I click the contextual link "Pin" in the "Lantern TCA" tile
    Then the "Lantern TCA" tile should be marked as pinned
    And the "Lantern FAQ" tile should be marked as pinned

    When I click the contextual link "Unpin" in the "Lantern FAQ" tile
    Then the "Lantern FAQ" tile should not be marked as pinned
    And the "Lantern TCA" tile should be marked as pinned

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |
