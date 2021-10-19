@api @terms @group-b
Feature: Global search
  As a user of the site I can find content through the global search.

  # Todo: This test runs with javascript enabled because in a non-javascript
  # environment, the dropdown facet is simply a list of links. Remove the
  # `@javascript` tag when the upstream issue in the Facets module is fixed.
  # Ref. https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5739
  # Ref. https://www.drupal.org/project/facets/issues/2937191
  @javascript
  Scenario: Anonymous user can find items
    Given the following collection:
      | title            | Molecular cooking collection |
      | logo             | logo.png                     |
      | moderation       | no                           |
      | topic            | Demography                   |
      | spatial coverage | Belgium                      |
      | state            | validated                    |
    And the following solutions:
      | title          | collection                   | description                                                                                                                          | topic      | spatial coverage | state     |
      | Spherification | Molecular cooking collection | Spherification is the culinary process of shaping a liquid into spheres                                                              | Demography | European Union   | validated |
      | Foam           | Molecular cooking collection | "The use of foam in cuisine has been used in many forms in the history of cooking:whipped cream, meringue, and mousse are all foams" |            |                  | validated |
    And news content:
      | title                 | body             | collection                   | topic                   | spatial coverage | state     |
      | El Celler de Can Roca | The best in town | Molecular cooking collection | Statistics and Analysis | Luxembourg       | validated |
      | Dummy news 1          | Dummy body       | Molecular cooking collection | E-inclusion             | Luxembourg       | validated |
      | Dummy news 2          | Dummy body       | Molecular cooking collection | E-inclusion             | Luxembourg       | validated |
      | Dummy news 3          | Dummy body       | Molecular cooking collection | E-inclusion             | Luxembourg       | validated |
      | Dummy news 4          | Dummy body       | Molecular cooking collection | E-inclusion             | Luxembourg       | validated |

    Given I am logged in as a user with the "authenticated" role
    # @todo The search page cache should be cleared when new content is added.
    # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3428
    And the cache has been cleared
    And I am on the homepage
    When I visit the search page
    And I wait until slim select is ready
    # All content is visible.
    Then I should see the "Molecular cooking collection" tile
    And I should see the "El Celler de Can Roca" tile
    And I should see the "Spherification" tile
    And I should see the "Foam" tile

    # Terms are sorted alphabetically
    And the slim select "topic" should contain the following options:
      # Parent term.
      | Info                      |
      # Child term.
      | - Statistics and Analysis |
      # Parent term.
      | Social and Political      |
      # Child terms.
      | - Demography              |
      | - E-inclusion             |
    # Since the topics are indented by a whitespace, and the whitespaces are trimmed in the step above, we are testing
    # the full response in order to ensure that the results are indented properly. The &nbsp; character below is the
    # printable space character.
    # @todo and WARNING. The following   character is supported by the old 3.4 selenium server. Change this in the
    # new infrastructure with the &nbsp; encoded character.
    And the response should contain "<option value=\"http://joinup.eu/ontology/topic/category#info\">Info</option>"
    And the response should contain "<option value=\"http://joinup.eu/ontology/topic#statistics-and-analysis\">- Statistics and Analysis</option>"
    And the response should contain "<option value=\"http://joinup.eu/ontology/topic/category#social-and-political\">Social and Political</option>"
    And the response should contain "<option value=\"http://joinup.eu/ontology/topic#demography\">- Demography</option>"
    And the response should contain "<option value=\"http://joinup.eu/ontology/topic#e-inclusion\">- E-inclusion</option></select>"
    And the "spatial coverage" select facet form should contain the following options:
      | Any location       |
      | Belgium (1)        |
      | European Union (1) |
      | Luxembourg (5)     |
    # Check that only one search field is available. In an earlier version of
    # Joinup there were two search fields, but this was confusing users.
    And there should be exactly 1 "search field" on the page

    When I select "Social and Political" from the "topic" slim select
    And I click Search in facets form
    Then the option with text "Social and Political" from slim select "topic" is selected
    And the slim select "topic" should contain the following options:
      | Info                      |
      | - Statistics and Analysis |
      | Social and Political      |
      | - Demography              |
      | - E-inclusion             |
    # The tiles appear because the parent term is selected even though they do not have a direct reference there.
    And I should see the "Dummy news 1" tile
    And I should see the "Dummy news 2" tile
    And I should see the "Dummy news 3" tile
    And I should see the "Dummy news 4" tile
    Then I remove "Social and Political" from the "topic" slim select

    # Test the topic facet. The space prefixing "Demography" is due to the hierarchy.

    When I select "Demography" from the "topic" slim select
    And I click Search in facets form
    Then the option with text "- Demography" from slim select "topic" is selected
    # The selected option moves to the last position by default.
    And the slim select "topic" should contain the following options:
      | Info                      |
      | - Statistics and Analysis |
      | Social and Political      |
      | - Demography              |
      | - E-inclusion             |

    #Then the option with text "Any location" from select facet form "spatial coverage" is selected
    And the "spatial coverage" select facet form should contain the following options:
      | Any location       |
      | Belgium (1)        |
      | European Union (1) |
    And I should see the "Molecular cooking collection" tile
    And I should see the "Spherification" tile
    But I should not see the "El Celler de Can Roca" tile
    And I should not see the "Foam" tile

    # Test the spatial coverage facet.
    When I select "Belgium" from the "spatial coverage" select facet form
    And I click Search in facets form
    Then the option with text "Belgium (1)" from select facet form "spatial coverage" is selected
    And the "spatial coverage" select facet form should contain the following options:
      | Any location       |
      | Belgium (1)        |
      | European Union (1) |
    Then the option with text "- Demography" from slim select "topic" is selected
    And the slim select "topic" should contain the following options:
      | Social and Political |
      | - Demography         |
    And I should see the "Molecular cooking collection" tile
    But I should not see the "El Celler de Can Roca" tile
    And I should not see the "Spherification" tile
    And I should not see the "Foam" tile

    # Select link in the 'type' facet.
    Given I am on the search page
    When I select "News (5)" from the "Content types" select facet form
    And I click Search in facets form
    Then the option with text "News (5)" from select facet form "Content types" is selected
    And the "Content types" select facet form should contain the following options:
      | Collection (1) |
      | News (5)       |
      | Solutions (2)  |

    When I select "Solutions (2)" option in the "Content types" select facet form
    And I click Search in facets form
    And I should see the following facet summary "News, Solutions"
    And the "Content types" select facet form should contain the following options:
      | Collection (1) |
      | News (5)       |
      | Solutions (2)  |
    And the slim select "topic" should contain the following options:
      | Info                      |
      | - Statistics and Analysis |
      | Social and Political      |
      | - Demography              |
      | - E-inclusion             |
    And the "spatial coverage" select facet form should contain the following options:
      | Any location       |
      | European Union (1) |
      | Luxembourg (5)     |
    And I should not see the "Molecular cooking collection" tile
    And I should see the "El Celler de Can Roca" tile
    But I should see the "Spherification" tile
    And I should see the "Foam" tile

    # Launch a text search.
    When I open the search bar by clicking on the search icon
    And I enter "Cooking" in the search bar and press enter
    Then I should see the "Molecular cooking collection" tile
    And I should see the "Foam" tile
    But I should not see the "Spherification" tile
    And I should not see the "El Celler de Can Roca" tile

  @javascript
  Scenario: Alphabetical order for the spatial coverage in the search page.
    Given the following owner:
      | name              | type    |
      | Responsible owner | Company |
    And the following contact:
      | name  | Go-to contact     |
      | email | go-to@example.com |
    And the following collections:
      | title            | description                                          | abstract                       | state     |
      | Collection alpha | <p>collection <strong>beta</strong> description.</p> | The collection gamma abstract. | validated |
      | Col for Sol      | <p>collection for the solution.</p>                  | The col for sol abstract.      | validated |
    And event content:
      | title             | short title       | body                                | spatial coverage | agenda         | location       | organisation        | scope         | keywords | collection       | state     |
      | Event Omega       | Event short delta | The epsilon event content.          | Greece           | Event agenda.  | Some place     | European Commission | International | Alphabet | Collection alpha | validated |
      | Alternative event | Alt event         | This event stays in the background. | Luxembourg       | To be planned. | Event location | Event organisation  |               |          | Collection alpha | validated |
    And document content:
      | title          | document type | short title          | body                                    | spatial coverage | keywords | collection       | state     |
      | Document omega | Document      | Document short delta | A document consists of epsilon strings. | Luxembourg       | Alphabet | Collection alpha | validated |

    When I visit the search page
    And the "spatial coverage" select facet form should contain the following options:
      | Any location   |
      | Greece (1)     |
      | Luxembourg (2) |
    When I select "Luxembourg" from the "spatial coverage" select facet form
    And I click Search in facets form
    Then the option with text "Luxembourg (2)" from select facet form "spatial coverage" is selected
    And I should see the text "Search Results (2)"
    # The countries are still sorted alphabetically even though the Luxembourg value is selected and has more results.
    And the "spatial coverage" select facet form should contain the following options:
      | Any location   |
      | Greece (1)     |
      | Luxembourg (2) |

  Scenario: Content can be found with a full-text search.
    Given the following owner:
      | name              | type    |
      | Responsible owner | Company |
    And the following contact:
      | name  | Go-to contact     |
      | email | go-to@example.com |
    And the following collections:
      | title            | description                                          | abstract                       | state     |
      | Collection alpha | <p>collection <strong>beta</strong> description.</p> | The collection gamma abstract. | validated |
      | Col for Sol      | <p>collection for the solution.</p>                  | The col for sol abstract.      | validated |
    And the following solutions:
      | title          | description                                                | keywords | owner             | contact information | collection  | state     |
      | Solution alpha | <p>This is the solution <strong>beta</strong> description. | Alphabet | Responsible owner | Go-to contact       | Col for Sol | validated |
    And the following releases:
      | title         | release number | release notes                               | keywords | is version of  | owner             | contact information | state     |
      | Release Alpha | 1              | <p>Release notes for <em>beta</em> changes. | Alphabet | Solution alpha | Responsible owner | Go-to contact       | validated |
    And the following distributions:
      | title              | description                                    | parent        | access url |
      | Distribution alpha | <p>A simple beta distribution description.</p> | Release Alpha | test.zip   |
    And the following licences:
      | title         | description                         |
      | Licence Alpha | A beta description for the licence. |
    And news content:
      | title      | headline            | body                      | keywords | collection       | state     |
      | News omega | News headline delta | The epsilon news content. | Alphabet | Collection alpha | validated |
    And event content:
      | title             | short title       | body                                | agenda         | location       | organisation        | scope         | keywords | collection       | solution       | state     |
      | Event Omega       | Event short delta | The epsilon event content.          | Event agenda.  | Some place     | European Commission | International | Alphabet |                  | Solution alpha | validated |
      | Alternative event | Alt event         | This event stays in the background. | To be planned. | Event location | Event organisation  |               |          | Collection alpha |                | validated |
    And document content:
      | title          | document type | short title          | body                                    | keywords | collection       | state     |
      | Document omega | Document      | Document short delta | A document consists of epsilon strings. | Alphabet | Collection alpha | validated |
    And discussion content:
      | title            | body                                                              | solution       | state     |
      | Discussion omega | <p>Does anybody has idea why this <em>epsilon</em> is everywhere? | Solution alpha | validated |
    And custom_page content:
      | title      | body                                     | collection       |
      | Page omega | This is just an epsilon but should work. | Collection alpha |
    And video content:
      | title       | body          | field_video                                 | collection       |
      | Video alpha | Slap like now | https://www.youtube.com/watch?v=JhGf8ZY0tN8 | Collection alpha |
    And users:
      | Username     | E-mail                      | First name | Family name | Organisation |
      | jenlyle      | jenessa.carlyle@example.com | Jenessa    | Carlyle     | Clyffco      |
      | ulyssesfrees | ulysses.freeman@example.com | Ulysses    | Freeman     | Omero snc    |

    When I visit the search page
    And the "Content types" select facet form should contain the following options:
      | Collections (2) |
      | Custom page (1) |
      | Discussion (1)  |
      | Document (1)    |
      | Events (2)      |
      | Licence (1)     |
      | News (1)        |
      | Release (1)     |
      | Solution (1)    |
      | Video (1)       |

    # "Alpha" is used in all the rdf entities titles.
    When I enter "Alpha" in the search bar and press enter
    Then I should see the text "Search Results (5)"
    And the page should show the tiles "Collection alpha, Solution alpha, Release Alpha, Licence Alpha, Video alpha"

    # "Omega" is used in all the node entities titles. Since the content of
    # custom pages is added to their collection, we also match the collection.
    When I enter "omega" in the search bar and press enter
    Then I should see the text "Search Results (6)"
    And the page should show the tiles "Collection alpha, News omega, Event Omega, Document omega, Discussion omega, Page omega"

    # "Beta" is used in all the rdf entities body fields.
    When I enter "beta" in the search bar and press enter
    Then I should see the text "Search Results (4)"
    And the page should show the tiles "Collection alpha, Solution alpha, Release Alpha, Licence Alpha"

    # "Epsilon" is used in all the node entities body fields.
    When I enter "epsilon" in the search bar and press enter
    Then the page should show the tiles "Collection alpha, News omega, Event Omega, Document omega, Discussion omega, Page omega"

    # "Alphabet" is used in all the keywords fields.
    When I enter "Alphabet" in the search bar and press enter
    Then the page should show the tiles "Solution alpha, Release Alpha, News omega, Event Omega, Document omega"

    # "Gamma" is used in the collection abstract.
    When I enter "gamma" in the search bar and press enter
    Then the page should show the tiles "Collection alpha"

    # "Delta" is used in headline and short titles.
    When I enter "delta" in the search bar and press enter
    Then the page should show the tiles "News omega, Event Omega, Document omega"

    # Search for the event fields: agenda, location, address, organisation, scope.
    When I enter "agenda" in the search bar and press enter
    Then the page should show the tiles "Event Omega"
    When I enter "location" in the search bar and press enter
    Then the page should show the tiles "Alternative event"
    When I enter "place" in the search bar and press enter
    Then the page should show the tiles "Event Omega"
    When I enter "organisation" in the search bar and press enter
    Then the page should show the tiles "Alternative event"
    When I enter "international" in the search bar and press enter
    Then the page should show the tiles "Event Omega"

    # The owner and contact information names should be indexed inside the solutions/releases they are linked to.
    When I enter "responsible" in the search bar and press enter
    Then the page should show the tiles "Solution alpha, Release Alpha"
    # Visit the homepage to be sure that the test fetches the correct updated page.
    When I go to the homepage
    And I enter "contact" in the search bar and press enter
    Then the page should show the tiles "Solution alpha, Release Alpha"

    # Users should be found by first name, family name and organisation.
    # @todo Enable when this ticket is implemented ISAICP-6575.
    # When I enter "Jenessa" in the search bar and press enter
    # Then the page should show the tiles "Jenessa Carlyle"
    # When I enter "freeman" in the search bar and press enter
    # Then the page should show the tiles "Ulysses Freeman"
    # When I enter "clyffco" in the search bar and press enter
    # Then the page should show the tiles "Jenessa Carlyle"
    # When I enter "Omero+snc" in the search bar and press enter
    # Then the page should show the tiles "Ulysses Freeman"

  Scenario: Advanced search
    # An advanced search link is shown in the header, except on the search page.
    Given I am on the homepage
    Then I should see the link "advanced search"
    Given I visit the collection overview
    Then I should see the link "Advanced search"
    When I click "Advanced search"
    Then I should be on the advanced search page
    But I should not see the link "Advanced search"

  Scenario: Collections and solutions are shown first in search results with the same relevance.
    Given collections:
      | title                           | description                         | state     |
      | Ornithology: the study of birds | Ornithology is a branch of zoology. | validated |
      | Husky Flying Xylophone          | A strange instrument.               | validated |
    And the following solution:
      | title       | Bird outposts in the wild            |
      | collection  | Ornithology: the study of birds      |
      | description | Exotic wings and where to find them. |
      | state       | validated                            |
    And custom_page content:
      | title           | body                                  | collection                      |
      | Disturbed birds | Flocks of trained pigeons flying off. | Ornithology: the study of birds |
    And news content:
      | title                               | body                            | collection                      | state     |
      | Chickens are small birds            | Birds domesticated in India.    | Ornithology: the study of birds | validated |
      | Found a xylophone from 1600 in Asia | Oldest instrument of this type. | Husky Flying Xylophone          | validated |
    And event content:
      | title         | body                   | collection                      | state     |
      | Bird spotting | Roosters crow at dawn. | Ornithology: the study of birds | validated |
    And discussion content:
      | title                             | body                    | collection                      | state     |
      | Best place to find an exotic bird | Somewhere exotic maybe? | Ornithology: the study of birds | validated |
    And user:
      | Username    | Bird watcher |
      | First name  | Bird         |
      | Family name | Birdman      |

    # The bird is the word... to search.
    When I enter "Bird" in the search bar and press enter
    Then I should see the following tiles in the correct order:
      | Ornithology: the study of birds   |
      | Bird outposts in the wild         |
      | Disturbed birds                   |
      | Chickens are small birds          |
      | Bird spotting                     |
      | Best place to find an exotic bird |
      # @todo Enable when this ticket is implemented ISAICP-6575.
      # | Bird Birdman                      |

  @clearStaticCache
  Scenario: Solutions and/or releases are found by their distribution keyword.
    Given the following licences:
      | title      |
      | Apache-2.0 |
      | LGPL       |
    And the following solution:
      | title | Zzolution |
      | state | validated |

    When I enter "ZzoluDistro" in the search bar and press enter
    Then I should see "No content found for your search."

    # Add distribution, child of solution.
    Given the following distribution:
      | title                    | ZzoluDistro                     |
      | parent                   | Zzolution                       |
      | description              | Übermensch foot size            |
      | access url               | http://example.com/zzolu-distro |
      | licence                  | Apache-2.0                      |
      | format                   | HTML                            |
      | representation technique | Datalog                         |

    When I enter "zzoludistro" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"
    When I enter "ubermensch" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"
    When I enter "zzolu-distro" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"
    When I enter "apache" in the search bar and press enter
    # Also the licence itself is retrieved.
    Then the page should show only the tiles "Apache-2.0,Zzolution"
    When I enter "HTML" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"
    When I enter "Datalog" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"

    Given I am logged in as a moderator
    When I go to the edit form of the "ZzoluDistro" distribution
    And I fill in "Title" with "DistroZzolu"
    And I fill in "Description" with "Nietzsche's"
    And I press the "Remove" button
    And I set a remote URL "http://example.com/guzzle" to "Access URL"
    And I select "LGPL" from "Licence"
    And I select "CSV" from "Format"
    And I select "Human Language" from "Representation technique"
    And I press "Save"
    Then I should see the heading "DistroZzolu"

    # Repeat the previous searches to prove that the initial keywords were
    # removed from the Search API index.
    Given I am an anonymous user
    When I enter "zzoludistro" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "ubermensch" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "zzolu-distro" in the search bar and press enter
    Then I should not see the text "Search Results"
    And I should see "No content found for your search."
    When I enter "apache" in the search bar and press enter
    Then the page should show only the tiles "Apache-2.0"
    When I enter "HTML" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "Datalog" in the search bar and press enter
    Then I should see "No content found for your search."

    # Search now with the new keywords.
    When I enter "distrozzolu" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"
    When I enter "nietzsche" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"
    When I enter "guzzle" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"
    When I enter "lGPL" in the search bar and press enter
    # Also the licence itself is retrieved.
    Then the page should show only the tiles "LGPL,Zzolution"
    When I enter "CSV" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"
    When I enter "Human Language" in the search bar and press enter
    Then the page should show only the tiles "Zzolution"

    Given I delete the "DistroZzolu" asset distribution

    # The parent solution has been re-indexed without distribution data.
    When I enter "distrozzolu" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "nietzsche" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "guzzle" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "lGPL" in the search bar and press enter
    Then the page should show only the tiles "LGPL"
    When I enter "CSV" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "Human Language" in the search bar and press enter
    Then I should see "No content found for your search."

    # Add a new distribution, child of a release.
    Given the following release:
      | title         | Releazz   |
      | state         | validated |
      | is version of | Zzolution |

    When I enter "ReleazzDistro" in the search bar and press enter
    Then I should see "No content found for your search."

    Given the following distribution:
      | title                    | ReleazzDistro                     |
      | parent                   | Releazz                           |
      | description              | Dracula                           |
      | access url               | http://example.com/releazz-distro |
      | licence                  | Apache-2.0                        |
      | format                   | HTML                              |
      | representation technique | Datalog                           |

    When I enter "releazzDistro" in the search bar and press enter
    Then the page should show only the tiles "Releazz"
    When I enter "dracula" in the search bar and press enter
    Then the page should show only the tiles "Releazz"
    When I enter "releazz-distro" in the search bar and press enter
    Then the page should show only the tiles "Releazz"
    When I enter "apache" in the search bar and press enter
    # Also the licence itself is retrieved.
    Then the page should show only the tiles "Apache-2.0,Releazz"
    When I enter "HTML" in the search bar and press enter
    Then the page should show only the tiles "Releazz"
    When I enter "Datalog" in the search bar and press enter
    Then the page should show only the tiles "Releazz"

    Given I am logged in as a moderator
    When I go to the edit form of the "ReleazzDistro" distribution
    And I fill in "Title" with "DistroReleazz"
    And I fill in "Description" with "Zorro"
    And I press the "Remove" button
    And I set a remote URL "http://example.com/mishmash" to "Access URL"
    And I select "LGPL" from "Licence"
    And I select "CSV" from "Format"
    And I select "Human Language" from "Representation technique"
    And I press "Save"

    # Repeat the previous searches to prove that the initial keywords were
    # removed from the Search API index.
    Given I am an anonymous user
    When I enter "releazzDistro" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "dracula" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "releazz-distro" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "apache" in the search bar and press enter
    Then the page should show only the tiles "Apache-2.0"
    When I enter "HTML" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "Datalog" in the search bar and press enter
    Then I should see "No content found for your search."

    # Search now with the new keywords.
    When I enter "dIstrOreleazz" in the search bar and press enter
    Then the page should show only the tiles "Releazz"
    When I enter "zoRRo" in the search bar and press enter
    Then the page should show only the tiles "Releazz"
    When I enter "mishMash" in the search bar and press enter
    Then the page should show only the tiles "Releazz"
    When I enter "LGpl" in the search bar and press enter
    # Also the licence itself is retrieved.
    Then the page should show only the tiles "LGPL,Releazz"
    When I enter "CSV" in the search bar and press enter
    Then the page should show only the tiles "Releazz"
    When I enter "Human Language" in the search bar and press enter
    Then the page should show only the tiles "Releazz"

    Given I delete the "DistroReleazz" asset distribution

    # The parent release has been re-indexed without distribution data.
    When I enter "dIstrOreleazz" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "zoRRo" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "mishMash" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "lGPL" in the search bar and press enter
    Then the page should show only the tiles "LGPL"
    When I enter "CSV" in the search bar and press enter
    Then I should see "No content found for your search."
    When I enter "Human Language" in the search bar and press enter
    Then I should see "No content found for your search."

  Scenario: Collections are found by their keywords.
    Given the following collection:
      | title    | Collection sample       |
      | keywords | unique, key-definitions |
      | state    | validated               |

    When I enter "key-definitions" in the search bar and press enter
    Then the page should show only the tiles "Collection sample"

    When I enter "unique" in the search bar and press enter
    Then the page should show only the tiles "Collection sample"

  @javascript
  Scenario: Users are able to select the sort order.
    Given collections:
      | title             | description       | state     |
      | Custom collection | Some custom data. | validated |
    And news content:
      | title                              | body                                                              | collection        | state     | created    | changed    |
      | Relativity is the word             | No one cares about the body.                                      | Custom collection | validated | 01/01/2019 | 03/08/2019 |
      | Relativity news: Relativity theory | I do care about the relativity keyword in the body.               | Custom collection | validated | 02/01/2019 | 02/08/2019 |
      | Absolutely nonesense               | Some news are not worth it but I will add relativity here anyway. | Custom collection | validated | 03/01/2019 | 01/08/2019 |

    When I am on the homepage
    And I enter "Relativity" in the search bar and press enter
    Then the option "Relevance" should be selected
    And I should see the text "Search Results (3)"
    And I should see the following tiles in the correct order:
      | Relativity news: Relativity theory |
      | Relativity is the word             |
      | Absolutely nonesense               |
    And I should be on "/search?keys=Relativity&sort_by=relevance"

    # @todo Enable when this ticket is implemented ISAICP-6575.
    # When I select "Creation Date" from "Sort by"
    # And I should see the following tiles in the correct order:
    #   | Absolutely nonesense               |
    #   | Relativity news: Relativity theory |
    #   | Relativity is the word             |
    # And I should be on "/search?keys=Relativity&sort_by=creation-date"
    #
    # When I select "Last Updated Date" from "Sort by"
    # And I should see the following tiles in the correct order:
    #   | Relativity is the word             |
    #   | Relativity news: Relativity theory |
    #   | Absolutely nonesense               |
    # And I should be on "/search?keys=Relativity&sort_by=last-updated-date"

  @javascript
  Scenario: Anonymous user can find facets summary
    Given the following collection:
      | title            | Radio cooking collection |
      | logo             | logo.png                 |
      | moderation       | no                       |
      | topic            | Demography               |
      | spatial coverage | Belgium                  |
      | state            | validated                |
    And the following solutions:
      | title    | collection               | description                                                                                                                          | topic      | spatial coverage | state     |
      | Spheres  | Radio cooking collection | Spherification is the culinary process of shaping a liquid into spheres                                                              | Demography | European Union   | validated |
      | Movistar | Radio cooking collection | "The use of foam in cuisine has been used in many forms in the history of cooking:whipped cream, meringue, and mousse are all foams" |            |                  | validated |
    And news content:
      | title           | body             | collection               | topic                   | spatial coverage | state     |
      | El Cabo da Roca | The best in town | Radio cooking collection | Statistics and Analysis | Luxembourg       | validated |
      | Funny news 1    | Dummy body       | Radio cooking collection | E-inclusion             | Luxembourg       | validated |
      | Funny news 2    | Dummy body       | Radio cooking collection | E-inclusion             | Luxembourg       | validated |
      | Funny news 3    | Dummy body       | Radio cooking collection | E-inclusion             | Luxembourg       | validated |
      | Funny news 4    | Dummy body       | Radio cooking collection | E-inclusion             | Luxembourg       | validated |

    Given I am logged in as a user with the "authenticated" role
    When I visit the search page
    And I select "Solutions (2)" from the "Content types" select facet form
    And I select "News (5)" option in the "Content types" select facet form
    And I click Search in facets form
    And I should see the following facet summary "News, Solutions"

    Then I click "Clear filters"
    And I select "News (5)" from the "Content types" select facet form
    And I click Search in facets form
    And I should see the following facet summary "News"

    # Check if facet summary was remove correctly.
    Then I click "Clear filters"
    And I select "News (5)" from the "Content types" select facet form
    And I select "Collection (1)" option in the "Content types" select facet form
    And I click Search in facets form
    And I should see the following facet summary "Collection, News"
    Then I should remove the following facet summary "News"
    And the page should show only the tiles "Radio cooking collection"
