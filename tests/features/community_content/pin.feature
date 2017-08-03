@api
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
      | content type | label |
      | event        | Event |
      | document     | Document   |
      | discussion   | Discussion |
      | news         | News       |