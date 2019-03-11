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
      | policy domain    | Demography          |
      | spatial coverage | Switzerland         |
      | state            | validated           |
    And solution:
      | title               | Inclined foundations                                  |
      | description         | Ways to construct foundations on hills and mountains. |
      | banner              | banner.jpg                                            |
      | logo                | logo.png                                              |
      | collection          | Chalet construction                                   |
      | state               | validated                                             |
    And discussion content:
      | title      | body                           | state     | collection          | solution             |
      | Room sizes | What are the ideal dimensions? | validated | Chalet construction |                      |
      | Terrace?   | Want a decent sized terrace    | validated |                     | Inclined foundations |
    And document content:
      | title       | body               | state     | collection          | solution             |
      | Ground plan | A classic design   | validated | Chalet construction |                      |
      | Rock types  | Ranked by hardness | validated |                     | Inclined foundations |
    And event content:
      | title                        | state     | collection          | solution             |
      | Opening of the winter season | validated | Chalet construction |                      |
      | Presenting DrillMaster X88   | validated |                     | Inclined foundations |
    And news content:
      | title             | body            | collection          | solution             | policy domain           | spatial coverage | state     |
      | Natural materials | Authentic feel  | Chalet construction |                      | Statistics and Analysis | Switzerland      | validated |
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
    # field on the collection homepage.
    When I go to the homepage of the "Chalet construction" collection
    Then the page should show the following chip:
      | Chalet construction |

    # Do a search without entering any keywords. Because the collection is shown
    # as a chip in the search field the search results should be filtered and
    # only show the content of the collection.
    When I press enter in the search field
    Then the page should show the tiles "Room sizes, Ground plan, Opening of the winter season, Natural materials, Inclined foundations"

    # Check that other types of pages in the collection also show the collection
    # as a chip.
    When I go to the "Room sizes" discussion
    Then the page should show the following chip:
      | Chalet construction |
    # It should be possible to remove the chip by clicking the delete button.
    When I press the remove button on the chip "Chalet construction"
    Then the page should not contain any chips

    When I go to the "Ground plan" document
    Then the page should show the following chip:
      | Chalet construction |
    # It should be possible to remove the chip by typing a backspace in the
    # search field.
    When I press backspace in the search field
    Then the page should not contain any chips

    When I go to the "Opening of the winter season" event
    Then the page should show the following chip:
      | Chalet construction |
    When I go to the "Natural materials" news
    Then the page should show the following chip:
      | Chalet construction |
    When I go to the "Resources" custom page
    Then the page should show the following chip:
      | Chalet construction |

    # Check that the name of the solution is shown as a chip in the search field
    # on the solution homepage.
    When I go to the homepage of the "Inclined foundations" solution
    Then the page should show the following chip:
      | Inclined foundations |

    # Do a search without entering any keywords. Because the solution is shown
    # as a chip in the search field the search results should be filtered and
    # only show the content of the solution.
    When I press enter in the search field
    Then the page should show the tiles "Pre-alpha, Presenting DrillMaster X88, Rock types, Still frozen, Terrace?"

    # Check that other types of pages in the solution also show the collection
    # as a chip.
    When I go to the "Terrace?" discussion
    Then the page should show the following chip:
      | Inclined foundations |
    # It should be possible to remove the chip by clicking the delete button.
    When I press the remove button on the chip "Inclined foundations"
    Then the page should not contain any chips

    When I go to the "Rock types" document
    Then the page should show the following chip:
      | Inclined foundations |
    # It should be possible to remove the chip by typing a backspace in the
    # search field.
    When I press backspace in the search field
    Then the page should not contain any chips

    When I go to the "Presenting DrillMaster X88" event
    Then the page should show the following chip:
      | Inclined foundations |
    When I go to the "Still frozen" news
    Then the page should show the following chip:
      | Inclined foundations |
    When I go to the "Geography" custom page
    Then the page should show the following chip:
      | Inclined foundations |
    When I go to the "Pre-alpha" release
    Then the page should show the following chip:
      | Inclined foundations |
    When I go to the "Zip archive" distribution
    Then the page should show the following chip:
      | Inclined foundations |
