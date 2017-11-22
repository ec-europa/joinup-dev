@api @email
Feature: Pinning content site-wide
  As a moderator of Joinup
  I want to pin content in the website
  So that important content has more visibility

  Background:
    Given the following collections:
      | title       | state     | pinned site-wide | creation date |
      | Risky Sound | validated | yes              | 2017-12-21    |
      | Tuna Moving | validated | no               | 2016-07-18    |
    And the following solutions:
      | title            | collection  | state     | pinned site-wide | creation date |
      | D minor          | Risky Sound | validated | yes              | 2017-12-22    |
      | Migration routes | Tuna Moving | validated | no               | 2017-01-03    |
    And users:
      | Username      | E-mail                    |
      | Burke Abraham | burke.abraham@example.com |
    And the following collection user memberships:
      | collection  | user          | roles       |
      | Risky Sound | Burke Abraham | facilitator |
      | Tuna Moving | Burke Abraham | facilitator |
    And the following solution user memberships:
      | solution         | user          | roles       |
      | D minor          | Burke Abraham | facilitator |
      | Migration routes | Burke Abraham | facilitator |

  Scenario Outline: Moderators can pin and unpin content site-wide.
    Given <content type> content:
      | title               | collection  | state     | pinned site-wide | visits | created    |
      | Loudest instruments | Risky Sound | validated | no               | 4390   | 2017-03-29 |
      | Handmade oboes      | Risky Sound | validated | yes              | 948    | 2017-04-25 |

    When I am an anonymous user
    And I go to the homepage
    # The pinned entities are always shown first.
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |
    And I should not see the contextual link "Pin site-wide" in the "Loudest instruments" tile
    And I should not see the contextual link "Pin site-wide" in the "Handmade oboes" tile
    And I should not see the contextual link "Unpin site-wide" in the "Loudest instruments" tile
    And I should not see the contextual link "Unpin site-wide" in the "Handmade oboes" tile

    When I am logged in as an "authenticated user"
    And I go to the homepage
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |
    And I should not see the contextual link "Pin site-wide" in the "Loudest instruments" tile
    And I should not see the contextual link "Pin site-wide" in the "Handmade oboes" tile
    And I should not see the contextual link "Unpin site-wide" in the "Loudest instruments" tile
    And I should not see the contextual link "Unpin site-wide" in the "Handmade oboes" tile

    # Facilitators cannot use the site-wide pin functionality.
    When I am logged in as "Burke Abraham"
    And I go to the homepage
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |
    And I should not see the contextual link "Pin site-wide" in the "Loudest instruments" tile
    And I should not see the contextual link "Pin site-wide" in the "Handmade oboes" tile
    And I should not see the contextual link "Unpin site-wide" in the "Loudest instruments" tile
    And I should not see the contextual link "Unpin site-wide" in the "Handmade oboes" tile

    When I am logged in as a moderator
    # Wait for contextual links to be generated. There is a session race condition that happens when a contextual link
    # has a CSRF token. The session will store the seed if not yet present, but if a new request is made before the
    # session is persisted, the seed won't be found and regenerated. For this reason, the already generated contextual
    # links with CSRF tokens won't be valid anymore.
    When I go to the homepage
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |
    And I should see the contextual link "Pin site-wide" in the "Loudest instruments" tile
    And I should see the contextual link "Unpin site-wide" in the "Handmade oboes" tile
    But I should not see the contextual link "Unpin site-wide" in the "Loudest instruments" tile
    And I should not see the contextual link "Pin site-wide" in the "Handmade oboes" tile

    When I click the contextual link "Pin site-wide" in the "Loudest instruments" tile
    Then I should see the success message "<label> Loudest instruments has been set as pinned content."
    When I go to the homepage
    # Pinned entities are sorted by creation date.
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |

    When I click the contextual link "Unpin site-wide" in the "Loudest instruments" tile
    Then I should see the success message "<label> Loudest instruments has been removed from the pinned contents."
    When I go to the homepage
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |

    When I click the contextual link "Unpin site-wide" in the "Handmade oboes" tile
    Then I should see the success message "<label> Handmade oboes has been removed from the pinned contents."
    When I go to the homepage
    # The two nodes are now sorted by their number of visits.
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Loudest instruments |
      | Handmade oboes      |

    Examples:
      | content type | label      |
      | event        | Event      |
      | document     | Document   |
      | discussion   | Discussion |
      | news         | News       |

  Scenario Outline: Moderators can pin and unpin collections and solutions site-wide.
    When I am an anonymous user
    And I click "<header link>" in the "Header" region
    Then I should see the following tiles in the correct order:
      | <pinned>   |
      | <unpinned> |
    And I should not see the contextual link "Pin site-wide" in the "<pinned>" tile
    And I should not see the contextual link "Unpin site-wide" in the "<unpinned>" tile

    When I am logged in as an "authenticated user"
    And I click "<header link>"
    Then I should see the following tiles in the correct order:
      | <pinned>   |
      | <unpinned> |
    And I should not see the contextual link "Pin site-wide" in the "<pinned>" tile
    And I should not see the contextual link "Unpin site-wide" in the "<unpinned>" tile

    # Facilitators cannot use the site-wide pin functionality.
    When I am logged in as "Burke Abraham"
    And I click "<header link>"
    Then I should see the following tiles in the correct order:
      | <pinned>   |
      | <unpinned> |
    And I should not see the contextual link "Pin site-wide" in the "<pinned>" tile
    And I should not see the contextual link "Unpin site-wide" in the "<unpinned>" tile

    When I am logged in as a moderator
    And I click "<header link>"
    Then I should see the following tiles in the correct order:
      | <pinned>   |
      | <unpinned> |
    And I should see the contextual link "Pin site-wide" in the "<unpinned>" tile
    And I should see the contextual link "Unpin site-wide" in the "<pinned>" tile
    But I should not see the contextual link "Unpin site-wide" in the "<unpinned>" tile
    And I should not see the contextual link "Pin site-wide" in the "<pinned>" tile

    When I click the contextual link "Unpin site-wide" in the "<pinned>" tile
    Then I should see the success message "<label> <pinned> has been removed from the pinned contents."
    When I click "<header link>"
    # Both the contents are unpinned now, so they are sorted by creation date descending.
    Then I should see the following tiles in the correct order:
      | <unpinned> |
      | <pinned>   |

    When I click the contextual link "Pin site-wide" in the "<unpinned>" tile
    Then I should see the success message "<label> <unpinned> has been set as pinned content."
    When I click "<header link>"
    Then I should see the following tiles in the correct order:
      | <unpinned> |
      | <pinned>   |

    Examples:
      | header link | pinned      | unpinned         | label      |
      | Collections | Risky Sound | Tuna Moving      | Collection |
      | Solutions   | D minor     | Migration routes | Solution   |
