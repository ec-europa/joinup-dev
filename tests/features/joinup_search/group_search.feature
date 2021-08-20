@api @terms
Feature: Search inside groups
  In order to quickly find content inside the group I am currently perusing
  As a user of Joinup
  I want to be able to launch a search limited to my current collection or solution

  Background:
    Given collection:
      | title            | Chalet construction |
      | logo             | logo.png            |
      | moderation       | no                  |
      | topic            | Demography          |
      | spatial coverage | Switzerland         |
      | state            | validated           |
    And solution:
      | title       | Inclined foundations                                  |
      | description | Ways to construct foundations on hills and mountains. |
      | banner      | banner.jpg                                            |
      | logo        | logo.png                                              |
      | collection  | Chalet construction                                   |
      | state       | validated                                             |
    And discussion content:
      | title      | body                           | state     | collection          | solution             |
      | Room sizes | What are the ideal dimensions? | validated | Chalet construction |                      |
      | Terrace?   | Want a decent sized terrace    | validated |                     | Inclined foundations |
    And document content:
      | title       | body               | state     | collection          | solution             |
      | Ground plan | A classic design   | validated | Chalet construction |                      |
      | Rock types  | Ranked by hardness | validated |                     | Inclined foundations |
    And event content:
      | title                        | body                              | state     | collection          | solution             |
      | Opening of the winter season | Ski resorts will open in December | validated | Chalet construction |                      |
      | Presenting DrillMaster X88   | Our most finely ground drill bit  | validated |                     | Inclined foundations |
    And news content:
      | title             | body            | collection          | solution             | topic                   | spatial coverage | state     |
      | Natural materials | Ground feel     | Chalet construction |                      | Statistics and Analysis | Switzerland      | validated |
      | Still frozen      | Maybe next week |                     | Inclined foundations |                         | Austria          | validated |
    And custom_page content:
      | title     | body                             | collection          | solution             |
      | Resources | Here are some interesting links. | Chalet construction |                      |
      | Geography | A collection of height maps.     |                     | Inclined foundations |
    And releases:
      | title     | release number | release notes              | is version of        | state     |
      | Pre-alpha | 0.0-alpha0     | Only works on flat ground. | Inclined foundations | validated |
    And distributions:
      | title       | description          | parent    | access url |
      | Zip archive | It has files inside. | Pre-alpha | test.zip   |

  @javascript
  Scenario: Group filters are shown as chips
    # Pages that do not belong to a collection or solution do not have chips in
    # the search field.
    When I am on the homepage
    Then the page should not contain any chips
    When I visit the collection overview
    Then the page should not contain any chips
    When I visit the solution overview
    Then the page should not contain any chips
    When I visit the contact form
    Then the page should not contain any chips

    # Check that the name of the collection is shown as a chip in the search
    # field on the collection homepage. The chips are initially hidden but will
    # appear after clicking on the search icon to open the search bar.
    When I go to the homepage of the "Chalet construction" collection
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Chalet construction |

    # Do a search without entering any keywords. Because the collection is shown
    # as a chip in the search field the search results should be filtered and
    # only show the content of the collection.
    When I submit the search by pressing enter
    Then the option with text "Chalet construction   (6)" from select facet "collection/solution" is selected
    And the page should show the tiles "Room sizes, Ground plan, Opening of the winter season, Natural materials, Resources, Inclined foundations"

    # Check that other types of pages in the collection also show the collection
    # as a chip.
    When I go to the "Room sizes" discussion
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Chalet construction |
    # It should be possible to remove the chip by clicking the delete button.
    When I press the remove button on the chip "Chalet construction"
    Then the page should not contain any chips

    When I go to the "Ground plan" document
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Chalet construction |
    # It should be possible to remove the chip by typing a backspace in the
    # search field.
    When I press backspace in the search bar
    Then the page should not contain any chips

    When I go to the "Opening of the winter season" event
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Chalet construction |
    When I go to the "Natural materials" news
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Chalet construction |
    When I go to the "Resources" custom page
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Chalet construction |

    # Check that the name of the solution is shown as a chip in the search field
    # on the solution homepage.
    When I go to the homepage of the "Inclined foundations" solution
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |

    # Do a search without entering any keywords. Because the solution is shown
    # as a chip in the search field the search results should be filtered and
    # only show the content of the solution.
    When I submit the search by pressing enter
    Then the option with text "Inclined foundations   (6)" from select facet "collection/solution" is selected
    Then the page should show the tiles "Pre-alpha, Presenting DrillMaster X88, Rock types, Still frozen, Geography, Terrace?"

    # Do a search with a keyword. The chip for the solution should be present
    # so the results are filtered by keyword and solution.
    Given I am on the homepage of the "Inclined foundations" solution
    When I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |
    When I enter "ground" in the search bar and press enter
    Then the option with text "Inclined foundations   (2)" from select facet "collection/solution" is selected
    And the page should show the tiles "Pre-alpha, Presenting DrillMaster X88"

    # Do a search with a keyword after removing the chip for the solution. The
    # results should only be filtered by keyword, not by solution.
    Given I am on the homepage of the "Inclined foundations" solution
    When I open the search bar by clicking on the search icon
    And I press the remove button on the chip "Inclined foundations"
    And I enter "ground" in the search bar and press enter
    Then the option with text "Any Collection or Solution" from select facet "collection/solution" is selected
    Then the page should show the tiles "Ground plan, Pre-alpha, Natural materials, Presenting DrillMaster X88"

    # Do a search with a keyword and removing the chip for the solution after
    # the keywords have been typed in. This should have the same result.
    Given I am on the homepage of the "Inclined foundations" solution
    When I open the search bar by clicking on the search icon
    And I enter "ground" in the search bar
    And I press the remove button on the chip "Inclined foundations"
    And I submit the search by pressing enter
    Then the page should show the tiles "Ground plan, Pre-alpha, Natural materials, Presenting DrillMaster X88"

    # Check that other types of pages in the solution also show the collection
    # as a chip.
    When I go to the "Terrace?" discussion
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |
    # It should be possible to remove the chip by clicking the delete button.
    When I press the remove button on the chip "Inclined foundations"
    Then the page should not contain any chips

    When I go to the "Rock types" document
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |
    # It should be possible to remove the chip by typing a backspace in the
    # search field.
    When I press backspace in the search bar
    Then the page should not contain any chips

    When I go to the "Presenting DrillMaster X88" event
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |
    When I go to the "Still frozen" news
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |
    When I go to the "Geography" custom page
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |
    When I go to the "Pre-alpha" release
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |
    When I go to the "Zip archive" distribution
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Inclined foundations |

  @clearStaticCache
  Scenario: Group search caching
    # Initially the cache is cold.
    When I go to the homepage of the "Chalet construction" collection
    Then the page should show the following chip:
      | Chalet construction |
    And the page should not be cached
    # A subsequent request should be served from cache.
    When I reload the page
    Then the page should show the following chip:
      | Chalet construction |
    And the page should be cached
    # Check that changing the name of the collection correctly invalidates the
    # cache.
    When I change the name of the "Chalet construction" collection to "Chalet destruction"
    And I go to the homepage of the "Chalet destruction" collection
    Then the page should show the following chip:
      | Chalet destruction |
    And the page should not be cached
    When I reload the page
    Then the page should show the following chip:
      | Chalet destruction |
    And the page should be cached

  @javascript
  Scenario: Group filters remain active in the search bar when viewing search results
    When I go to the homepage of the "Chalet construction" collection
    And I open the search bar by clicking on the search icon
    Then the page should show the following chip:
      | Chalet construction |
    When I enter "ground" in the search bar and press enter
    Then the option with text "Chalet construction   (2)" from select facet "collection/solution" is selected
    And the page should show the tiles "Ground plan, Natural materials"
    And the page should show the following chip in the "Search bar":
      | Chalet construction |
    When I press the remove button on the chip "Chalet construction"
    And I submit the search by pressing enter
    Then the page should not contain any chips
    And the page should show the tiles "Ground plan, Pre-alpha, Natural materials, Presenting DrillMaster X88"

  @javascript
  Scenario: Group filter chips appear in search bar after selecting them in facets
    When I visit the search page
    Then the option with text "Any Collection or Solution" from select facet "collection/solution" is selected
    And I should see 12 tiles

    When I open the search bar by clicking on the search icon
    Then the page should not contain any chips

    When I select "Inclined foundations" from the "collection/solution" select facet
    Then the option with text "Inclined foundations   (6)" from select facet "collection/solution" is selected
    And I should see 6 tiles

    When I open the search bar by clicking on the search icon
    Then the page should show the following chip in the "Search bar":
      | Inclined foundations |

    # The filter chip should remain active when doing another search.
    When I enter "ground" in the search bar and press enter
    Then the option with text "Inclined foundations   (2)" from select facet "collection/solution" is selected
    And I should see 2 tiles
    When I open the search bar by clicking on the search icon
    Then the page should show the following chip in the "Search bar":
      | Inclined foundations |
