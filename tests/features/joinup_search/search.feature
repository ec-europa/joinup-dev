@api @terms @group-d
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

    Given I am logged in as a user with the "authenticated" role
    # @todo The search page cache should be cleared when new content is added.
    # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3428
    And the cache has been cleared
    When I visit the search page
    # All content is visible.
    Then I should see the "Molecular cooking collection" tile
    And I should see the "El Celler de Can Roca" tile
    And I should see the "Spherification" tile
    And I should see the "Foam" tile
    # Facets should be in place.
    And the option with text "Any topic" from select facet "topic" is selected
    And the "topic" select facet should contain the following options:
      | Any topic                     |
      | Demography (2)              |
      | Statistics and Analysis (1) |
    And the option with text "Any location" from select facet "spatial coverage" is selected
    And the "spatial coverage" select facet should contain the following options:
      | Any location         |
      | Belgium (1)        |
      | European Union (1) |
      | Luxembourg (1)     |
    # Check that only one search field is available. In an earlier version of
    # Joinup there were two search fields, but this was confusing users.
    And there should be exactly 1 "search field" on the page

    # Test the topic facet.
    When I select "Demography" from the "topic" select facet
    Then the option with text "Demography (2)" from select facet "topic" is selected
    # The selected option moves to the last position by default.
    And the "topic" select facet should contain the following options:
      | Any topic                     |
      | Statistics and Analysis (1) |
      | Demography (2)              |
    Then the option with text "Any location" from select facet "spatial coverage" is selected
    And the "spatial coverage" select facet should contain the following options:
      | Any location         |
      | Belgium (1)        |
      | European Union (1) |
    And I should see the "Molecular cooking collection" tile
    And I should see the "Spherification" tile
    But I should not see the "El Celler de Can Roca" tile
    And I should not see the "Foam" tile

    # Test the spatial coverage facet.
    When I select "Belgium" from the "spatial coverage" select facet
    Then the option with text "Belgium (1)" from select facet "spatial coverage" is selected
    And the "spatial coverage" select facet should contain the following options:
      | Any location         |
      | Belgium (1)        |
      | European Union (1) |
    Then the option with text "Demography (1)" from select facet "topic" is selected
    And the "topic" select facet should contain the following options:
      | Any topic        |
      | Demography (1) |
    And I should see the "Molecular cooking collection" tile
    But I should not see the "El Celler de Can Roca" tile
    And I should not see the "Spherification" tile
    And I should not see the "Foam" tile

    # Reset the search by visiting again the search page.
    Given I am on the search page
    Then I should see the text "Content types" in the "Left sidebar" region

    # Select link in the 'type' facet.
    When I check the "News (1)" checkbox from the "Content types" facet
    Then the "News" content checkbox item should be selected
    And the "Content types" checkbox facet should allow selecting the following values "Collection (1), Solutions (2), News (1)"

    When I check the "Solutions (2)" checkbox from the "Content types" facet
    Then the "Solutions" content checkbox item should be selected
    And the "News" content checkbox item should be selected
    Then the "Content types" checkbox facet should allow selecting the following values "Collection (1), Solutions (2), News (1)"
    And the "topic" select facet should contain the following options:
      | Any topic                     |
      | Demography (1)              |
      | Statistics and Analysis (1) |
    And the "spatial coverage" select facet should contain the following options:
      | Any location         |
      | European Union (1) |
      | Luxembourg (1)     |
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
    And the "spatial coverage" select facet should contain the following options:
      | Any location     |
      | Greece (1)     |
      | Luxembourg (2) |
    When I select "Luxembourg" from the "spatial coverage" select facet
    Then the option with text "Luxembourg (2)" from select facet "spatial coverage" is selected
    # The countries are still sorted alphabetically even though the Luxembourg value is selected and has more results.
    And the "spatial coverage" select facet should contain the following options:
      | Any location     |
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
    Then the "Content types" checkbox facet should allow selecting the following values "Collections (2), Solution (1), News (1), Events (2), Document (1), Discussion (1), Release (1), Custom page (1), Licence (1), Video (1)"

    # "Alpha" is used in all the rdf entities titles.
    When I enter "Alpha" in the search bar and press enter
    Then the page should show the tiles "Collection alpha, Solution alpha, Release Alpha, Licence Alpha, Video alpha"

    # "Omega" is used in all the node entities titles. Since the content of
    # custom pages is added to their collection, we also match the collection.
    When I enter "omega" in the search bar and press enter
    Then the page should show the tiles "Collection alpha, News omega, Event Omega, Document omega, Discussion omega, Page omega"

    # "Beta" is used in all the rdf entities body fields.
    When I enter "beta" in the search bar and press enter
    Then the page should show the tiles "Collection alpha, Solution alpha, Release Alpha, Licence Alpha"

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
    When I enter "Jenessa" in the search bar and press enter
    Then the page should show the tiles "Jenessa Carlyle"
    When I enter "freeman" in the search bar and press enter
    Then the page should show the tiles "Ulysses Freeman"
    When I enter "clyffco" in the search bar and press enter
    Then the page should show the tiles "Jenessa Carlyle"
    When I enter "Omero+snc" in the search bar and press enter
    Then the page should show the tiles "Ulysses Freeman"

  Scenario: Advanced search
    # An advanced search link is shown in the header, except on the home page
    # and the search page.
    Given I am on the homepage
    Then I should not see the link "Advanced search"
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
      | Bird Birdman                      |

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
    Then I should see "No content found for your search."
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
    And I should see the following tiles in the correct order:
      | Relativity news: Relativity theory |
      | Relativity is the word             |
      | Absolutely nonesense               |
    And I should be on "/search?keys=Relativity&sort_by=relevance"

    Given I select "Creation date" from "Sort by"
    Then I should see the following tiles in the correct order:
      | Absolutely nonesense               |
      | Relativity news: Relativity theory |
      | Relativity is the word             |
    And I should be on "/search?keys=Relativity&sort_by=creation-date"

    Given I select "Last updated date" from "Sort by"
    Then I should see the following tiles in the correct order:
      | Relativity is the word             |
      | Relativity news: Relativity theory |
      | Absolutely nonesense               |
    And I should be on "/search?keys=Relativity&sort_by=last-updated-date"

  Scenario: When not entering any keywords the last updated content should be shown first
    Given collections:
      | title             | description       | state     |
      | World collection  | Some custom data. | validated |
    And news content:
      | title                              | body                                                              | collection        | state     | created    | changed    |
      | Relativity is the mood             | No one cares about the body.                                      | World collection  | validated | 01/01/2020 | 03/08/2020 |
      | Absolutely fantastic               | Some news are not worth it but I will add relativity here anyway. | World collection  | validated | 03/01/2020 | 01/08/2020 |
      | Relativity news: Relativity car    | I do care about the relativity keyword in the body.               | World collection  | validated | 02/01/2020 | 02/08/2020 |

    When I visit the search page
    # Filter on the content type so that we have a stable search result, which
    # is not affected by unrelated content (such as user accounts created during
    # the installation of the test site).
    And I check the "News (3)" checkbox from the "Content types" facet
    # Check that the default sorting option remains unchanged. It should still
    # show "Relevance" even though the results are actually sorted by last
    # updated time. The reason for this is that the user should still be free to
    # enter keywords and get relevant results, without having to manually change
    # the sorting options.
    Then the option with text "Relevance" from select "Sort by" is selected
    And I should see the following tiles in the correct order:
      | Relativity is the mood             |
      | Relativity news: Relativity car    |
      | Absolutely fantastic               |
