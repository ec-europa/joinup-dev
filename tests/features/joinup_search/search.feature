@api
Feature: Global search
  As a user of the site I can find content through the global search.

  Scenario: Anonymous user can find items
    Given the following solutions:
      | title          | description                                                                                                                        | state     |
      | Spherification | Spherification is the culinary process of shaping a liquid into spheres                                                            | validated |
      | Foam           | The use of foam in cuisine has been used in many forms in the history of cooking:whipped cream, meringue, and mousse are all foams | validated |
      # Taxonomies are not yet implemented, so uncomment this after #ISAICP-2545 is done
      # | spatial coverage | http://publications.europa.eu/resource/authority/country/EUR            |
    And the following collection:
      | title      | Molecular cooking collection |
      | logo       | logo.png                     |
      | moderation | no                           |
      | affiliates | Spherification, Foam         |
      | state      | validated                    |
    And news content:
      | title                 | body             | collection                   | state     |
      | El Celler de Can Roca | The best in town | Molecular cooking collection | validated |

    Given I am logged in as a user with the "authenticated" role
    When I am at "/search"
    # All content visible
    Then I should see the text "Molecular cooking collection"
    Then I should see the text "El Celler de Can Roca"
    Then I should see the text "Spherification"
    Then I should see the text "Foam"

    # Select link in the 'type' facet.
    Then I click "solution" in the "Left sidebar" region
    # @todo Re-enable this check when the tile view mode created.
    # (The default view mode of solutions holds a link to it's collection)
    # Then I should not see the text "Molecular cooking collection"
    Then I should not see the text "El Celler de Can Roca"
    Then I should see the text "Spherification"
    Then I should see the text "Foam"

  @terms @javascript
  Scenario: Content can be found with a full-text search.
    Given the following collections:
      | title            | description                                                  | abstract                       | state     |
      | Collection alpha | <p>This is the collection <strong>beta</strong> description. | The collection gamma abstract. | validated |
    And the following solutions:
      | title          | description                                                | keywords | state     |
      | Solution alpha | <p>This is the solution <strong>beta</strong> description. | Alphabet | validated |
    And the following releases:
      | title         | release notes                               | keywords | is version of  | state     |
      | Release Alpha | <p>Release notes for <em>beta</em> changes. | Alphabet | Solution alpha | validated |
    And the following distributions:
      | title              | description                                    | parent        | access url |
      | Distribution alpha | <p>A simple beta distribution description.</p> | Release Alpha | test.zip   |
    And the following licences:
      | title         | description                         |
      | Licence Alpha | A beta description for the licence. |
    And news content:
      | title      | kicker            | content                | keywords | collection       | state     |
      | News omega | News kicker delta | The beta news content. | Alphabet | Collection alpha | validated |
    And event content:
      | title       | short title       | content                 | agenda        | location       | additional info address | organisation         | scope         | keywords | solution       | state     |
      | Event Omega | Event short delta | The beta event content. | Event agenda. | Event location | Event address           | Epsilon organisation | International | Alphabet | Solution alpha | validated |
    And document content:
      | title          | type     | short title          | content                              | keywords | collection       | state     |
      | Document omega | Document | Document short delta | A document consists of beta strings. | Alphabet | Collection alpha | validated |
    And discussion content:
      | title            | content                                                        | collection     | state     |
      | Discussion omega | <p>Does anybody has idea why this <em>beta</em> is everywhere? | Solution alpha | validated |
    And newsletter content:
      | title            | content                            |
      | Newsletter omega | Talking about these beta contents. |
    And custom_page content:
      | title      | content                              | collection       |
      | Page omega | This is just a beta but should work. | Collection alpha |
    #And users:
    #  | Username | E-mail | First name | Family name | Organisation |

    When I am at "/search?keys=Alpha"
    Then I should see the "Collection alpha" tile
    And I should see the "Solution alpha" tile
    And I should see the "Release Alpha" tile
    And I should see the "Distribution alpha" tile
    And I should see the text "Licence Alpha"
    But I should not see the "News omega" tile
    And I should not see the "Event Omega" tile
    And I should not see the "Document omega" tile
    And I should not see the "Discussion omega" tile
    And I should not see the "Page omega" tile
    And I should not see the text "Newsletter omega"

    When I am at "/search?keys=omega"
    Then I should see the "News omega" tile
    And I should see the "Event Omega" tile
    And I should see the "Document omega" tile
    And I should see the "Discussion omega" tile
    And I should see the "Page omega" tile
    And I should see the text "Newsletter omega"
    But I should not see the "Collection alpha" tile
    And I should not see the "Solution alpha" tile
    And I should not see the "Release Alpha" tile
    And I should not see the "Distribution alpha" tile
    And I should not see the text "Licence Alpha"

    When I am at "/search?keys=Alphabet"
    Then I should see the "Solution alpha" tile
    And I should see the "Release Alpha" tile
    And I should see the "News omega" tile
    And I should see the "Document omega" tile
    And I should see the "Event Omega" tile
    But I should not see the "Collection alpha" tile
    And I should not see the "Distribution alpha" tile
    And I should not see the text "Licence Alpha"
    And I should not see the "Discussion omega" tile
    And I should not see the "Page omega" tile
    And I should not see the text "Newsletter omega"