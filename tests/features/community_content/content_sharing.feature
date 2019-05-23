@api @email
Feature: Sharing content between collections
  As a privileged user
  I want to share content between collections
  So that useful information has more visibility

  @javascript
  Scenario Outline: Users can share content in the collections they are member of.
    Given users:
      | Username      | E-mail                    |
      | Sara Barber   | sara.barber@example.com   |
      | Deryck Lynn   | deryck.lynn@example.com   |
      | Marjolein Rye | marjolein.rye@example.com |
    And the following collections:
      | title        | state     |
      | Classic Rock | validated |
      | Hip-Hop      | validated |
      | Power ballad | validated |
      | Drum'n'Bass  | validated |
    And news content:
      | title                          | kicker                         | collection   | state     |
      | New D'n'B compilation released | New D'n'B compilation released | Classic Rock | validated |
      | Old-school line-up concert     | Old-school line-up concert     | Hip-Hop      | validated |
    And discussion content:
      | title                       | collection   | state     |
      | Rockabilly is still rocking | Classic Rock | validated |
    And the following collection user memberships:
      | collection   | user          |
      | Hip-Hop      | Marjolein Rye |
      | Classic Rock | Sara Barber   |
      | Hip-Hop      | Sara Barber   |
      | Drum'n'Bass  | Sara Barber   |
    And <content type> content:
      | title               | collection | state     |
      | Interesting content | Hip-Hop    | validated |

    # Anonymous users can share only in social networks.
    When I am an anonymous user
    And I go to the content page of the type "<content type>" with the title "Interesting content"
    And I click "Share"
    Then a modal should open
    And the following fields should not be present "Classic Rock, Hip-Hop, Power ballad, Drum'n'Bass"

    # This "authenticated user" is not member of any collections, so he can
    # share only in social networks.
    When I am logged in as an "authenticated user"
    And I go to the content page of the type "<content type>" with the title "Interesting content"
    And I click "Share"
    Then a modal should open
    And the following fields should not be present "Classic Rock, Hip-Hop, Power ballad, Drum'n'Bass"

    # A member of a single collection which is the one where the content was
    # created can share in social networks only.
    When I am logged in as "Marjolein Rye"
    And I go to the content page of the type "<content type>" with the title "Interesting content"
    And I click "Share"
    Then a modal should open
    And the following fields should not be present "Classic Rock, Hip-Hop, Power ballad, Drum'n'Bass"

    # A collection member should see the link.
    When I am logged in as "Sara Barber"
    And I go to the "Rockabilly is still rocking" discussion
    Then I should see the heading "Rockabilly is still rocking"
    When I click "Share"
    Then a modal should open
    # Collections the user is member of should be available.
    And the following fields should be present "Hip-Hop, Drum'n'Bass"
    # While the original content collection and collections the user is not
    # member of should not be shown.
    But the following fields should not be present "Classic Rock, Power ballad"

    # Verify on another node the correctness of the share tool.
    When I go to the content page of the type "<content type>" with the title "Interesting content"
    And I click "Share"
    Then a modal should open
    And the following fields should be present "Classic Rock, Drum'n'Bass"
    And the following fields should not be present "Hip-Hop, Power ballad"

    # Share the content in a collection.
    When I check "Classic Rock"
    And I press "Share" in the "Modal buttons" region
    And I wait for AJAX to finish
    Then I should see the success message "Item was shared in the following collections: Classic Rock."
    # Verify that the collections where the content has already been shared are
    # not shown anymore in the list.
    When I click "Share"
    Then a modal should open
    Then the following fields should be present "Drum'n'Bass"
    And the following fields should not be present "Classic Rock, Hip-Hop, Power ballad"

    # The shared content should be shown amongst the other content tiles.
    When I go to the homepage of the "Classic Rock" collection
    Then the page should show only the tiles "New D'n'B compilation released, Rockabilly is still rocking, Interesting content"

    # It should not be shared in the other collection.
    When I go to the homepage of the "Drum'n'Bass" collection
    Then I should not see the "Interesting content" tile

    # Content can be un-shared only by facilitators of the collections they
    # have been shared in.
    When I am an anonymous user
    And I go to the homepage of the "Classic Rock" collection
    Then I should see the "Interesting content" tile
    And I should not see the contextual link "Unshare" in the "Interesting content" tile

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Classic Rock" collection
    Then I should see the "Interesting content" tile
    And I should not see the contextual link "Unshare" in the "Interesting content" tile

    When I am logged in as a facilitator of the "Power ballad" collection
    And I go to the homepage of the "Classic Rock" collection
    Then I should see the "Interesting content" tile
    And I should not see the contextual link "Unshare" in the "Interesting content" tile

    When I am logged in as a facilitator of the "Classic Rock" collection
    And I go to the homepage of the "Classic Rock" collection
    Then I should see the "Interesting content" tile
    And I should see the contextual link "Unshare" in the "Interesting content" tile
    When I click the contextual link "Unshare" in the "Interesting content" tile
    Then a modal will open
    And I should see the text "Unshare Interesting content from"
    Then the following fields should be present "Classic Rock"
    And the following fields should not be present "Drum'n'Bass, Hip-Hop, Power ballad"

    # Unshare the content.
    When I check "Classic Rock"
    And I press "Submit" in the "Modal buttons" region
    And I wait for AJAX to finish

    # I should still be on the same page, but the collection content should be
    # changed. The "Interesting content" should no longer be visible.
    Then I should see the success message "Item was unshared from the following collections: Classic Rock."
    And the page should show only the tiles "New D'n'B compilation released, Rockabilly is still rocking"

    # Verify that the content is again shareable.
    When I go to the content page of the type "<content type>" with the title "Interesting content"
    When I click "Share"
    Then a modal should open
    And the following fields should be present "Classic Rock"
    And the following fields should not be present "Drum'n'Bass, Hip-Hop, Power ballad"

    # Verify that the unshare link is not present when the content is not
    # shared anywhere.
    When I go to the homepage of the "Hip-Hop" collection
    Then I should see the "Interesting content" tile
    And I should not see the contextual link "Unshare" in the "Interesting content" tile

    # The content should obviously not shared in the other collection too.
    When I go to the homepage of the "Drum'n'Bass" collection
    Then I should not see the "Interesting content" tile

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |

  @javascript
  Scenario Outline: Share/Unshare contextual link should only be visible to facilitators
    Given collections:
      | title      | state     |
      | Westeros   | validated |
      | Essos city | validated |
    And "<content type>" content:
      | title       | collection | state     |
      | Iron throne | Westeros   | validated |
    Given users:
      | Username       | E-mail                     | Roles     |
      | Jamie Lanister | jamie.lanister@example.com | moderator |
      | John Snow      | john.snow@example.com      |           |
      | Arya Stark     | arya.stark@example.com     |           |
    And the following collection user memberships:
      | collection | user       | roles       |
      | Westeros   | John snow  | facilitator |
      | Essos city | John snow  | member      |
      | Essos city | Arya Stark | facilitator |

    When I am logged in as "Arya Stark"
    And I click "Keep up to date"
    Then I should see the contextual link "Share" in the "Iron throne" tile
    And I should not see the contextual link "Unshare" in the "Iron throne" tile

    When I am logged in as "John Snow"
    And I click "Keep up to date"
    Then I should see the contextual link "Share" in the "Iron throne" tile
    But I should not see the contextual link "Unshare" in the "Iron throne" tile

    # Normally we would share directly from the Keep up to date page. However, a styling issue is preventing the
    # checkbox to be located.
    # @see: ISAICP-5245.
    # And I click "Keep up to date"
    # Then I should see the contextual link "Share" in the "Iron throne" tile
    # And I click the contextual link "Share" in the "Iron throne" tile
    When I go to the content page of the type "<content type>" with the title "Iron throne"
    And I click "Share"
    Then a modal should open
    And the following fields should be present "Essos city"
    When I check "Essos city"
    And I press "Share" in the "Modal buttons" region
    And I wait for AJAX to finish
    Then I should see the success message "Item was shared in the following collections: Essos city."

    When I am on the homepage
    And I click "Keep up to date"
    Then I should see the contextual link "Share" in the "Iron throne" tile
    # Simple members can still not unshare content from collections.
    But I should not see the contextual link "Unshare" in the "Iron throne" tile

    When I am logged in as "Arya Stark"
    And I click "Keep up to date"
    Then I should see the contextual link "Share" in the "Iron throne" tile
    # Link vary by user og role since the 2 users up to now have the same permissions outside og.
    And I should see the contextual link "Unshare" in the "Iron throne" tile
    When I click the contextual link "Unshare" in the "Iron throne" tile
    Then a modal should open
    And the following fields should be present "Essos city"

    When I am logged in as "Jamie Lanister"
    And I click "Keep up to date"
    Then I should see the contextual link "Share" in the "Iron throne" tile
    # Moderators should be able to unshare from every group the content is shared in.
    And I should see the contextual link "Unshare" in the "Iron throne" tile
    When I click the contextual link "Unshare" in the "Iron throne" tile
    Then a modal should open
    And the following fields should be present "Essos city"

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |

  @javascript
  Scenario Outline: Shared content should show visual cues in the collections they are shared.
    Given collections:
      | title | state     |
      | Earth | validated |
      | Mars  | validated |
      | Venus | validated |
    And <content type> content:
      | title         | collection | shared in   | state     |
      | Earth content | Earth      | Mars        | validated |
      | Mars content  | Mars       |             | validated |
      | Venus content | Venus      | Earth, Mars | validated |

    When I go to the homepage of the "Earth" collection
    Then I should see the "Earth content" tile
    And I should see the "Venus content" tile
    And the "Venus content" tile should be marked as shared from "Venus"
    And the "Earth content" tile should not be marked as shared

    When I go to the homepage of the "Mars" collection
    Then I should see the "Earth content" tile
    And I should see the "Mars content" tile
    And I should see the "Venus content" tile
    And the "Earth content" tile should be marked as shared from "Earth"
    And the "Venus content" tile should be marked as shared from "Venus"
    But the "Mars content" tile should not be marked as shared

    When I go to the homepage of the "Venus" collection
    Then I should see the "Venus content" tile
    And the "Venus content" tile should not be marked as shared

    When I go to the homepage
    And I click "Events, discussions, news ..."
    And the "Earth content" tile should not be marked as shared
    And the "Mars content" tile should not be marked as shared
    And the "Venus content" tile should not be marked as shared

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |

  Scenario: Shared pinned content is erroneously shown first.
    Given collections:
      | title         | state     |
      | Milky Way     | validated |
      | Chocolate Way | validated |
    And "document" content:
      | title                 | collection    | shared in     | state     | created    | pinned |
      | Milky Way content     | Milky Way     | Chocolate Way | validated | 2017-06-04 | yes    |
      | Chocolate Way content | Chocolate Way |               | validated | 2017-06-05 | no     |
    When I go to the homepage of the "Chocolate Way" collection
    Then I should see the following tiles in the correct order:
      | Chocolate Way content |
      | Milky Way content     |

  @javascript
  Scenario Outline: The sharing options should be shown in a modal window.
    Given collections:
      | title   | state     |
      | Secrets | validated |
      | Gossip  | validated |
    And <content type> content:
      | title                 | collection | state     |
      | An unshareable secret | Secrets    | validated |
    And users:
      | Username        | E-mail                |
      | Sanjica Sauvage | sanjisauv@example.com |
    And the following collection user memberships:
      | collection | user            |
      | Secrets    | Sanjica Sauvage |
      | Gossip     | Sanjica Sauvage |
    And I am an anonymous user
    When I go to the content page of the type "<content type>" with the title "An unshareable secret"
    And I click "Share"
    Then a modal should open
    And I should see the following lines of text:
      | Facebook |
      | Twitter  |
      | Linkedin |

    # Check that the collection the content is shared in is immediately shown in the "Shared in" tiles.
    Given I am logged in as "Sanjica Sauvage"
    When I go to the content page of the type "<content type>" with the title "An unshareable secret"
    And I click "Share"
    Then a modal should open
    And I should see the following lines of text:
      | Share in                    |
      | Facebook                    |
      | Twitter                     |
      | Linkedin                    |
      | Other collections in Joinup |
    When I check "Gossip"
    And I press "Share" in the "Modal buttons" region
    And I wait for AJAX to finish
    Then I should see the success message "Item was shared in the following collections: Gossip."
    And I should see the heading "Shared in"
    And I should see the "Gossip" tile

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |
