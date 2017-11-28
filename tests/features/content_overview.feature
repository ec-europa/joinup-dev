@api @email
Feature: Content Overview

  Scenario: Ensure access to content overview landing page, called "Keep up to date".
    Given I am an anonymous user
    # Anonymous users land on the homepage.
    Then I should see the link "Events, discussions, news ..."
    When I click "Events, discussions, news ..."
    # Visually hidden heading.
    Then I should see the heading "Keep up to date"
    # Check that all logged in users can see and access the overview page as well.
    # However, authenticated users land on their profile, so they need to use the menu.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Keep up to date"
    When I click "Keep up to date"
    # Visually hidden heading.
    Then I should see the heading "Keep up to date"

  @terms
  Scenario: View content overview as an anonymous user
    Given users:
      | Username     | First name | Family name | E-mail               |
      | batbull      | Simba      | Hobson      | simba3000@hotmail.de |
      | welshbuzzard | Titus      | Nicotera    | nicotito@example.org |
      | hatchingegg  | Korinna    | Morin       | korimor@example.com  |
    And the following collections:
      | title             | description        | state     | moderation |
      | Rumble collection | Sample description | validated | yes        |
    And "event" content:
      | title           | collection        | state     | created           |
      | Seventh Windows | Rumble collection | validated | 2018-10-03 4:21am |
    And "news" content:
      | title            | collection        | state     | author       | created           |
      | The Playful Tale | Rumble collection | validated | batbull      | 2018-10-03 4:26am |
      | Night of Shadow  | Rumble collection | proposed  | welshbuzzard | 2018-10-03 4:26am |
    And "document" content:
      | title             | collection        | state     | created           |
      | History of Flight | Rumble collection | validated | 2018-10-03 4:19am |
    And "discussion" content:
      | title            | collection        | state     | author      | created           |
      | The Men's Female | Rumble collection | validated | hatchingegg | 2018-10-03 4:18am |

    # Check that visiting as a moderator does not create cache for all users.
    When I am logged in as a user with the "moderator" role
    And I am on the homepage
    And I click "Keep up to date"
    Then I should see the following facet items "Discussion, Document, Event, News" in this order
    And I should not see the following facet items "Collection"
    And I should see the following tiles in the correct order:
      | The Playful Tale  |
      | Seventh Windows   |
      | History of Flight |
      | The Men's Female  |
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

    When I click the "Document" content tab
    Then I should see the following facet items "Document, Discussion, Event, News" in this order
    And I should see the following tiles in the correct order:
      | History of Flight |

    # Check page for authenticated users.
    When I am logged in as a user with the "authenticated" role
    And I am on the homepage
    And I click "Keep up to date"
    Then I should see the "Seventh Windows" tile
    And I should see the "The Playful Tale" tile
    And I should see the "History of Flight" tile
    And I should see the "The Men's Female" tile
    But I should not see the "Rumble collection" tile
    And I should not see the "Night of Shadow" tile

    # Check the page for anonymous users.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Events, discussions, news ..."
    When I click "Events, discussions, news ..."
    Then I should see the "Seventh Windows" tile
    And I should see the "The Playful Tale" tile
    And I should see the "History of Flight" tile
    And I should see the "The Men's Female" tile
    But I should not see the "Rumble collection" tile
    And I should not see the "Night of Shadow" tile

  Scenario: Content overview active trail should persist on urls with arguments.
    Given I am an anonymous user
    And I visit "/keep-up-to-date?a=1"
    Then "Keep up to date" should be the active item in the "Header menu" menu

  Scenario: Users are able to filter content they have created or that is featured site-wide.
    Given users:
      | Username        | E-mail                       |
      | michaelanewport | michaela.newport@example.com |
      | nenaroberts     | nena.roberts@example.com     |
    And the following collections:
      | title            | state     |
      | Timely Xylophone | validated |
    And "event" content:
      | title            | collection       | state     |
      | Sticky Vegetable | Timely Xylophone | validated |
    And "news" content:
      | title            | collection       | state     | author          | featured |
      | Early Avenue     | Timely Xylophone | validated | michaelanewport | yes      |
      | Itchy Artificial | Timely Xylophone | validated | nenaroberts     | no       |
    And "document" content:
      | title             | collection       | state     |
      | Limousine Scarlet | Timely Xylophone | validated |
    And "discussion" content:
      | title                  | collection       | state     | author          | featured |
      | Hideous Dreaded Monkey | Timely Xylophone | validated | michaelanewport | yes      |

    When I am logged in as "michaelanewport"
    And I click "Keep up to date"
    Then the "My content" inline facet should allow selecting the following values "Featured content (2), My content (2)"
    When I click "My content" in the "My content" inline facet
    Then I should see the following tiles in the correct order:
      | Early Avenue           |
      | Hideous Dreaded Monkey |
    And the "My content" inline facet should allow selecting the following values "Featured content (2), All content"
    # Regression test to ensure that the facets are cached by user.
    # Subsequent page loads of the content page would lead to cached facets
    # to be leaked to other users.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3777
    When I click "All content" in the "My content" inline facet
    Then the "My content" inline facet should allow selecting the following values "Featured content (2), My content (2)"

    When I am logged in as "nenaroberts"
    And I click "Keep up to date"
    Then the "My content" inline facet should allow selecting the following values "Featured content (2), My content (1)"
    When I click "My content" in the "My content" inline facet
    Then I should see the following tiles in the correct order:
      | Itchy Artificial |
    And the "My content" inline facet should allow selecting the following values "Featured content (2), All content"
    # Regression test to ensure that the facets are cached by user.
    # Subsequent page loads of the content page would lead to cached facets
    # to be leaked to other users.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3777
    When I click "All content" in the "My content" inline facet
    Then the "My content" inline facet should allow selecting the following values "Featured content (2), My content (1)"

    When I am an anonymous user
    And I am on the homepage
    And I click "Events, discussions, news ..."
    Then I should see the following tiles in the correct order:
      | Sticky Vegetable       |
      | Early Avenue           |
      | Itchy Artificial       |
      | Limousine Scarlet      |
      | Hideous Dreaded Monkey |
    And the "My content" inline facet should allow selecting the following values "Featured content (2)"
    When I click "Featured content" in the "My content" inline facet
    Then I should see the following tiles in the correct order:
      | Early Avenue           |
      | Hideous Dreaded Monkey |
    And the "My content" inline facet should allow selecting the following values "All content"

  Scenario: Users should be able to use additional filters on events.
    Given users:
      | Username        | E-mail                       |
      | claricemitchell | clarice.mitchell@example.com |
      | jeffreypayne    | jeffrey.payne@example.com    |
    And collection:
      | title | Barbaric Avenue |
      | state | validated       |
    And event content:
      | title                | collection      | start date   | end date            | created    | state     | author          |
      | Bitter Finger        | Barbaric Avenue | now -1 years | now -1 years +1 day | now -4 day | validated | claricemitchell |
      | Frozen Barbershop    | Barbaric Avenue | now -1 day   | now +1 day          | now -3 day | validated | claricemitchell |
      | Frozen Breeze        | Barbaric Avenue | now +2 day   | now +4 day          | now -2 day | validated | claricemitchell |
      | Flying Official Fish | Barbaric Avenue | now -3 day   | now -1 day          | now        | validated | jeffreypayne    |
    # Technical: use a separate step to create an event associated to the anonymous user.
    And event content:
      | title          | collection      | start date  | end date    | created    | state     |
      | Autumn Boiling | Barbaric Avenue | now +1 week | now +1 week | now -5 day | validated |
    And discussion content:
      | title           | collection      | state     | created   |
      | Purple Poseidon | Barbaric Avenue | validated | yesterday |

    When I am logged in as claricemitchell
    And I am on the homepage
    And I click "Keep up to date"
    Then I should see the following tiles in the correct order:
      | Flying Official Fish |
      | Purple Poseidon      |
      | Frozen Breeze        |
      | Frozen Barbershop    |
      | Bitter Finger        |
      | Autumn Boiling       |
   # The date second level facet appears only after filtering for events.
    And I should not see the link "Upcoming events (3)"

    When I click "Event"
    Then I should see the tiles in the correct order:
      | Autumn Boiling       |
      | Frozen Breeze        |
      | Frozen Barbershop    |
      | Flying Official Fish |
      | Bitter Finger        |
    And the "Event date" inline facet should allow selecting the following values "My events (3), Past events (3), Upcoming events (2)"

    # @todo The 'Frozen Barbershop' is a current event and should also be shown here.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4133
    When I click "Upcoming events" in the "Event date" inline facet
    Then I should see the following tiles in the correct order:
      | Frozen Breeze  |
      | Autumn Boiling |
    And the "Event date" inline facet should allow selecting the following values "My events (3), Past events (3), All events"

    When I click "My events" in the "Event date" inline facet
    Then I should see the following tiles in the correct order:
      | Frozen Breeze     |
      | Frozen Barbershop |
      | Bitter Finger     |
    And the "Event date" inline facet should allow selecting the following values "Past events (3), Upcoming events (2), All events"

    # @todo The 'Frozen Barbershop' is a current event and should not be shown here.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4133
    When I click "Past events" in the "Event date" inline facet
    Then I should see the following tiles in the correct order:
      | Frozen Barbershop    |
      | Flying Official Fish |
      | Bitter Finger        |
    And the "Event date" inline facet should allow selecting the following values "My events (3), Upcoming events (2), All events"

    # The second level facet is deactivated together with its parent.
    When I click "Event"
    Then I should see the following tiles in the correct order:
      | Flying Official Fish |
      | Purple Poseidon      |
      | Frozen Breeze        |
      | Frozen Barbershop    |
      | Bitter Finger        |
      | Autumn Boiling       |

    When I am logged in as jeffreypayne
    And I am on the homepage
    And I click "Keep up to date"
    And I click "Event"
    Then I should see the tiles in the correct order:
      | Autumn Boiling       |
      | Frozen Breeze        |
      | Frozen Barbershop    |
      | Flying Official Fish |
      | Bitter Finger        |
    And the "Event date" inline facet should allow selecting the following values "Past events (3), Upcoming events (2), My events (1)"

    # Tests facets with a different user to verify that cache leaks are prevented.
    When I click "Upcoming events" in the "Event date" inline facet
    Then I should see the following tiles in the correct order:
      | Frozen Breeze  |
      | Autumn Boiling |
    And the "Event date" inline facet should allow selecting the following values "Past events (3), My events (1), All events"

    When I click "My events" in the "Event date" inline facet
    Then I should see the following tiles in the correct order:
      | Flying Official Fish |
    And the "Event date" inline facet should allow selecting the following values "Past events (3), Upcoming events (2), All events"

    When I click "Past events" in the "Event date" inline facet
    Then I should see the following tiles in the correct order:
      | Frozen Barbershop    |
      | Flying Official Fish |
      | Bitter Finger        |
    And the "Event date" inline facet should allow selecting the following values "Upcoming events (2), My events (1), All events"

    When I am an anonymous user
    And I am on the homepage
    And I click "Events, discussions, news ..."
    And I click "Event"
    Then the "Event date" inline facet should allow selecting the following values "Past events (3), Upcoming events (2)"

    When I click "Upcoming events" in the "Event date" inline facet
    Then the "Event date" inline facet should allow selecting the following values "Past events (3), All events"

    When I click "Past events" in the "Event date" inline facet
    Then the "Event date" inline facet should allow selecting the following values "Upcoming events (2), All events"
