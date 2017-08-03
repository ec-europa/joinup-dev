@api @email
Feature: Pinning content inside collections
  As a facilitator of a collection
  I want to pin content at the top of the collection homepage
  So that important content has more visibility

  Scenario Outline: Facilitators can pin and unpin content inside their collections.
    Given the following collections:
      | title         | state     |
      | Orange Wrench | validated |
      | Cloudy Beam   | validated |
    And discussion content:
      | title                       | collection    | state     |
      | What is the HEX for orange? | Orange Wrench | validated |
    And <content type> content:
      | title              | collection    | state     | sticky |
      | Very important     | Orange Wrench | validated | 1      |
      | Useful information | Orange Wrench | validated | 0      |
    And users:
      | Username        | E-mail                      |
      | Rozanne Minett  | rozanne.minett@example.com  |
      | Tyron Ingram    | tyron.ingram@example.com    |
      | Andy Cross      | andy.cross@example.com      |
      | Xanthia Gilbert | xanthia.gilbert@example.com |
    And the following collection user memberships:
      | collection    | user            | roles       |
      | Orange Wrench | Rozanne Minett  | facilitator |
      | Orange Wrench | Tyron Ingram    |             |
      | Cloudy Beam   | Andy Cross      | facilitator |
      | Cloudy Beam   | Xanthia Gilbert |             |

    When I am an anonymous user
    And I go to the homepage of the "Orange Wrench" collection
    Then I should see the following tiles in the correct order:
      | Very important              |
      | What is the HEX for orange? |
      | Useful information          |
    Then I should not see the contextual link "Pin" in the "Useful information" tile
    Then I should not see the contextual link "Unpin" in the "Very important" tile

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Orange Wrench" collection
    Then I should not see the contextual link "Pin" in the "Useful information" tile
    Then I should not see the contextual link "Unpin" in the "Very important" tile

    # Members and facilitators of other collections cannot pin nor unpin.
    When I am logged in as "Andy Cross"
    And I go to the homepage of the "Orange Wrench" collection
    Then I should not see the contextual link "Pin" in the "Useful information" tile
    Then I should not see the contextual link "Unpin" in the "Very important" tile
    When I am logged in as "Xanthia Gilbert"
    And I go to the homepage of the "Orange Wrench" collection
    Then I should not see the contextual link "Pin" in the "Useful information" tile
    Then I should not see the contextual link "Unpin" in the "Very important" tile

    # Collection members cannot pin nor unpin content.
    When I am logged in as "Tyron Ingram"
    And I go to the homepage of the "Orange Wrench" collection
    Then I should not see the contextual link "Pin" in the "Useful information" tile
    Then I should not see the contextual link "Unpin" in the "Very important" tile

    # Facilitators of the collection the content belongs to can pin/unpin.
    When I am logged in as "Rozanne Minett"
    And I go to the homepage of the "Orange Wrench" collection
    Then I should see the contextual link "Pin" in the "Useful information" tile
    Then I should see the contextual link "Unpin" in the "Very important" tile

    When I click the contextual link "Unpin" in the "Very important" tile
    Then I should see the success message "<label> Very important has been unpinned in the collection Orange Wrench."
    Then I should see the following tiles in the correct order:
      | What is the HEX for orange? |
      | Useful information          |
      | Very important              |

    When I click the contextual link "Pin" in the "Useful information" tile
    Then I should see the success message "<label> Useful information has been pinned in the collection Orange Wrench."
    Then I should see the following tiles in the correct order:
      | Useful information          |
      | What is the HEX for orange? |
      | Very important              |

    Examples:
      | content type | label      |
      | event        | Event      |
      | document     | Document   |
      | discussion   | Discussion |
      | news         | News       |

  @javascript
  Scenario Outline: Pinned content tiles should show a visual cue only in their collection homepage.
    Given the following collections:
      | title             | state     |
      | Gloomy Lantern    | validated |
      | Digital scarecrow | validated |
    And <content type> content:
      | title         | collection     | state     | sticky | shared in         |
      | Lantern FAQs  | Gloomy Lantern | validated | 1      | Digital scarecrow |
      | Lantern terms | Gloomy Lantern | validated | 0      |                   |

    When I go to the homepage of the "Gloomy Lantern" collection
    Then the "Lantern FAQs" tile should be marked as pinned
    But the "Lantern terms" tile should not be marked as pinned

    # When shared in other collection, content shouldn't show the pin icon.
    When I go to the homepage of the "Digital scarecrow" collection
    Then the "Lantern FAQs" tile should not be marked as pinned

    When I am at "/search"
    Then the "Lantern FAQs" tile should not be marked as pinned

    # Verify that changes in the pinned state are reflected to the tile.
    When I am logged in as a facilitator of the "Gloomy Lantern" collection
    When I go to the homepage of the "Gloomy Lantern" collection
    Then the "Lantern FAQs" tile should be marked as pinned
    But the "Lantern terms" tile should not be marked as pinned

    When I click the contextual link "Pin" in the "Lantern terms" tile
    Then the "Lantern terms" tile should be marked as pinned
    And the "Lantern FAQs" tile should be marked as pinned

    When I click the contextual link "Unpin" in the "Lantern FAQs" tile
    Then the "Lantern FAQs" tile should not be marked as pinned
    And the "Lantern terms" tile should be marked as pinned

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |

  Scenario Outline: Content cannot be pinned inside solutions.
    Given the following solution:
      | title | Space Silver |
      | state | validated    |
    And <content type> content:
      | title        | solution     | state     |
      | To be pinned | Space Silver | validated |

    When I am logged in as a facilitator of the "Space Silver" solution
    And I go to the homepage of the "Space Silver" solution
    Then I should not see the contextual link "Pin" in the "To be pinned" tile

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |
