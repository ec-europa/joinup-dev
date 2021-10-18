@api @group-g @wip
Feature: Pinning content to the front page
  As a moderator of Joinup
  I want to pin content in the website
  So that important content has more visibility

  Background:
    Given the following owner:
      | name              |
      | Timofei Håkansson |
    And the following contact:
      | name  | Arushi Papke     |
      | email | aripap@yahoo.com |
    And the following collections:
      | title       | state     | creation date | owner             | contact information |
      | Risky Sound | validated | 2017-12-21    | Timofei Håkansson | Arushi Papke        |
      | Tuna Moving | validated | 2018-02-28    |                   |                     |
    And the following solutions:
      | title            | collection  | state     | creation date |
      | D minor          | Risky Sound | validated | 2017-12-22    |
      | Migration routes | Tuna Moving | validated | 2018-01-31    |
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
    And the following "rdf" entities are pinned to the front page:
      | title       |
      | D minor     |
      | Risky Sound |

  Scenario Outline: Moderators can pin and unpin content to the front page.
    Given <content type> content:
      | title               | collection  | state     | visits | created    |
      | Loudest instruments | Risky Sound | validated | 4390   | 2017-03-29 |
      | Handmade oboes      | Risky Sound | validated | 948    | 2017-04-25 |
    And the following "content" entities are pinned to the front page:
      | title          |
      | Handmade oboes |

    When I am an anonymous user
    And I go to the homepage
    # The pinned entities are always shown first.
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |
    And I should not see the contextual link "Pin to front page" in the "Loudest instruments" tile
    And I should not see the contextual link "Pin to front page" in the "Handmade oboes" tile
    And I should not see the contextual link "Unpin from front page" in the "Loudest instruments" tile
    And I should not see the contextual link "Unpin from front page" in the "Handmade oboes" tile

    When I am logged in as an "authenticated user"
    And I go to the homepage
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |
    And I should not see the contextual link "Pin to front page" in the "Loudest instruments" tile
    And I should not see the contextual link "Pin to front page" in the "Handmade oboes" tile
    And I should not see the contextual link "Unpin from front page" in the "Loudest instruments" tile
    And I should not see the contextual link "Unpin from front page" in the "Handmade oboes" tile

    # Facilitators cannot use the pin to front page functionality.
    When I am logged in as "Burke Abraham"
    And I go to the homepage
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |
    And I should not see the contextual link "Pin to front page" in the "Loudest instruments" tile
    And I should not see the contextual link "Pin to front page" in the "Handmade oboes" tile
    And I should not see the contextual link "Unpin from front page" in the "Loudest instruments" tile
    And I should not see the contextual link "Unpin from front page" in the "Handmade oboes" tile

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
    And I should see the contextual link "Pin to front page" in the "Loudest instruments" tile
    And I should see the contextual link "Unpin from front page" in the "Handmade oboes" tile
    But I should not see the contextual link "Unpin from front page" in the "Loudest instruments" tile
    And I should not see the contextual link "Pin to front page" in the "Handmade oboes" tile

    When I click the contextual link "Pin to front page" in the "Loudest instruments" tile
    Then I should see the success message "<label> Loudest instruments has been set as pinned content."
    When I go to the homepage
    # Pinned entities are sorted by creation date.
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |

    When I click the contextual link "Unpin from front page" in the "Loudest instruments" tile
    Then I should see the success message "<label> Loudest instruments has been removed from the pinned contents."
    When I go to the homepage
    Then I should see the following tiles in the correct order:
      | D minor             |
      | Risky Sound         |
      | Handmade oboes      |
      | Loudest instruments |

    When I click the contextual link "Unpin from front page" in the "Handmade oboes" tile
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

  Scenario Outline: Moderators can pin and unpin collections and solutions to the front page.
    When I am an anonymous user
    And I am on the homepage
    And I click "<header link>" in the "Header" region
    Then I should see the following tiles in the correct order:
      | <pinned>   |
      | <unpinned> |
    And I should not see the contextual link "Pin to front page" in the "<pinned>" tile
    And I should not see the contextual link "Unpin from front page" in the "<unpinned>" tile

    When I am logged in as an "authenticated user"
    And I click "<header link>"
    Then I should see the following tiles in the correct order:
      | <pinned>   |
      | <unpinned> |
    And I should not see the contextual link "Pin to front page" in the "<pinned>" tile
    And I should not see the contextual link "Unpin from front page" in the "<unpinned>" tile

    # Facilitators cannot use the pin to front page functionality.
    When I am logged in as "Burke Abraham"
    And I click "<header link>"
    Then I should see the following tiles in the correct order:
      | <pinned>   |
      | <unpinned> |
    And I should not see the contextual link "Pin to front page" in the "<pinned>" tile
    And I should not see the contextual link "Unpin from front page" in the "<unpinned>" tile

    When I am logged in as a moderator
    And I click "<header link>"
    Then I should see the following tiles in the correct order:
      | <pinned>   |
      | <unpinned> |
    And I should see the contextual link "Pin to front page" in the "<unpinned>" tile
    And I should see the contextual link "Unpin from front page" in the "<pinned>" tile
    But I should not see the contextual link "Unpin from front page" in the "<unpinned>" tile
    And I should not see the contextual link "Pin to front page" in the "<pinned>" tile

    When I click the contextual link "Unpin from front page" in the "<pinned>" tile
    Then I should see the success message "<label> <pinned> has been removed from the pinned contents."
    When I click "<header link>"
    # Both the contents are unpinned now, so they are sorted by creation date descending.
    Then I should see the following tiles in the correct order:
      | <unpinned> |
      | <pinned>   |

    When I click the contextual link "Pin to front page" in the "<unpinned>" tile
    Then I should see the success message "<label> <unpinned> has been set as pinned content."
    When I click "<header link>"
    Then I should see the following tiles in the correct order:
      | <unpinned> |
      | <pinned>   |

    Examples:
      | header link | pinned      | unpinned         | label      |
      | Collections | Risky Sound | Tuna Moving      | Collection |
      | Solutions   | D minor     | Migration routes | Solution   |

  Scenario: Front page menu access.
    Given I am logged in as a user with the "authenticated" role
    When I am on the homepage
    Then I should not see the contextual link "Edit pinned items"

    Given I am logged in as a user with the "moderator" role
    When I am on the homepage
    Then I should see the contextual link "Edit pinned items"

  @javascript
  Scenario: Front page menu re-ordering.
    Given news content:
      | title                | collection  | state     | visits | created    |
      | Entry to be disabled | Risky Sound | validated | 0      | 2017-03-29 |
      | Some low visit news  | Risky Sound | validated | 948    | 2017-04-25 |
    And the following "content" entities are pinned to the front page:
      | title                |
      | Some low visit news  |
      | Entry to be disabled |

    When I am logged in as a moderator
    And I am on the homepage
    Then I should see the following tiles in the correct order:
      | D minor              |
      | Risky Sound          |
      | Some low visit news  |
      | Entry to be disabled |

    When I click the contextual link "Edit pinned items" in the "Content" region
    Then I should see the heading "Front page pinned items"
    And I should not see "Edit" in the "D minor" row
    And I should not see "Edit" in the "Risky Sound" row
    And I should not see "Edit" in the "Some low visit news" row
    And I should not see "Edit" in the "Entry to be disabled" row

    # Disable the 'Entry to be disabled' menu entry.
    Given I uncheck the material checkbox in the "Entry to be disabled" table row
    And I drag the "D minor" table row down
    # Move it to the top.
    And I drag the "Some low visit news" table row up
    And I drag the "Some low visit news" table row up
    And I drag the "Some low visit news" table row up
    Then the draggable menu table should be:
      | title                |
      | Some low visit news  |
      | Entry to be disabled |
      | D minor              |
      | Risky Sound          |

    When I press "Save"
    And I go to the homepage

    # Todo: the following test fails due to an infrastructure problem on CPHP.
    #   It should be enabled again after moving to the new CI infrastructure.
    # Ref. https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5763
    # Then I should see the following tiles in the correct order:
    # | Some low visit news  |
    # | D minor              |
    # | Risky Sound          |
    # # The next item is still shown due to popularity but not according to the pinned order.
    # | Entry to be disabled |

    # Delete the first item.
    When I click the contextual link "Edit pinned items" in the "Content" region
    And I click "Delete"
    And I press "Delete"
    # Delete the first item.
    And I click the contextual link "Edit pinned items" in the "Content" region
    And I click "Delete"
    And I press "Delete"
    # Delete the first item.
    And I click the contextual link "Edit pinned items" in the "Content" region
    And I click "Delete"
    And I press "Delete"
    # Delete the first item.
    And I click the contextual link "Edit pinned items" in the "Content" region
    And I click "Delete"
    And I press "Delete"

    When I click the contextual link "Edit pinned items" in the "Content" region
    Then I should see the text "There are no pinned items. Start by pinning an entity to the front page."

  @javascript
  Scenario: Contextual links keep working after relogging
    Given users:
      | Username        | E-mail              | Roles     |
      | Jocelyn Modpeel | jocymod@example.com | moderator |
    And I am logged in as "Jocelyn Modpeel"
    And I visit the collection overview page
    When I click the contextual link "Pin to front page" in the "Tuna Moving" tile
    Then I should see the success message "Collection Tuna Moving has been set as pinned content."
    When I click the contextual link "Unpin from front page" in the "Tuna Moving" tile
    Then I should see the success message "Collection Tuna Moving has been removed from the pinned contents."

    # Log out and back in. This should clear the cached CSRF tokens and the
    # contextual links should keep working.
    When I log out
    And I am logged in as "Jocelyn Modpeel"
    And I visit the collection overview page
    And I click the contextual link "Pin to front page" in the "Tuna Moving" tile
    Then I should see the success message "Collection Tuna Moving has been set as pinned content."

  # Regression test for a bug that caused moderators to see links to pin owners
  # and contact information entities to the front page in about pages.
  Scenario: Owners and contact information cannot be pinned to the front page.
    Given I am logged in as a moderator
    When I go to the about page of "Risky Sound"
    Then I should not see the contextual link "Pin to front page"
