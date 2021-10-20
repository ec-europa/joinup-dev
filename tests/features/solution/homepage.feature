@api
Feature: Solution homepage
  In order get an idea of what a solution is about
  As a user of the website
  I need to be able see an introduction of the solution on its homepage

  Scenario: The solution homepage shows basic information about the solution
    Given the following solution:
      | title       | Petri net                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
      | description | "<p>A <b>Petri net</b>, also known as a <b>place/transition (PT) net</b>, is one of several <a href=\"#mathematical\">mathematical</a> modeling languages for the description of distributed systems. It is a class of discrete event dynamic system. A Petri net is a directed bipartite graph, in which the nodes represent transitions (i.e. events that may occur, represented by bars) and places (i.e. conditions, represented by circles). The directed arcs describe which places are pre- and/or postconditions for which transitions (signified by arrows). Some sources state that Petri nets were invented in August 1939 by Carl Adam Petri — at the age of 13 — for the purpose of describing chemical processes.</p>" |
      | logo        | logo.png                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
      | banner      | banner.jpg                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
      | state       | validated                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
    When I go to the homepage of the "Petri net" solution
    # Checking for HTML text ensures that any HTML elements in the abstract are correctly stripped.
    Then the page should contain the html text "A Petri net, also known as a place/transition (PT) net, is one of several mathematical modeling languages for the description of distributed systems."
    # The text should be split on a word boundary after 500 characters, followed by an ellipsis.
    And the page should contain the html text "(signified by arrows)…"
    # There should be a link to the about page.
    And I should see the link "Read more"
    # The page should be cacheable.
    And the page should be cacheable
    # The description itself should be stripped of unsightly links.
    But I should not see the link "mathematical"
    # The 'Read more' link leads to the About page.
    When I click "Read more"
    Then I should see the heading "About Petri net"

  # This is a regression test for the entities that include a hashmark on their Uri.
  # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3225
  Scenario: Regression test for Uris that include a '#'.
    Given the following solution:
      | uri         | http://solution/example1/test#        |
      | title       | Information sharing protocols         |
      | description | Handling information sharing securely |
      | logo        | logo.png                              |
      | banner      | banner.jpg                            |
      | state       | validated                             |
    When I go to the homepage of the "Information sharing protocols" solution
    Then I should see the heading "Information sharing protocols"
    And I should not see the text "Page not found"

  @terms
  Scenario: Custom pages should not be visible on the solution homepage
    Given the following solution:
      | title            | Jira restarters                      |
      | description      | Rebooting solves all issues          |
      | documentation    | text.pdf                             |
      | content creation | registered users                     |
      | landing page     | http://foo-example.com/landing       |
      | webdav creation  | no                                   |
      | webdav url       | http://joinup.eu/solution/foo/webdav |
      | wiki             | http://example.wiki/foobar/wiki      |
      | state            | validated                            |
    And news content:
      | title                             | body                             | solution        | topic                   | spatial coverage | state     |
      | Jira will be down for maintenance | As always, during business hours | Jira restarters | Statistics and Analysis | Luxembourg       | validated |
    And custom_page content:
      | title            | body                                       | solution        |
      | Maintenance page | Jira is re-indexing. Go and drink a coffee | Jira restarters |
    When I go to the homepage of the "Jira restarters" solution
    Then a tour should be available
    And I should see the "Jira will be down for maintenance" tile
    And I should not see the "Maintenance page" tile

  Scenario: A link to the first collection a solution is affiliated to should be shown in the solution header.
    Given collections:
      | title              | state     |
      | Disappointed Steel | validated |
      | Random Arm         | validated |
    And the following solutions:
      | title       | state     | collections                   |
      | Robotic arm | validated | Disappointed Steel            |
      | ARM9        | validated | Disappointed Steel,Random Arm |

    When I go to the homepage of the "Robotic arm" solution
    Then I should see the link "Disappointed Steel"

    When I go to the homepage of the "ARM9" solution
    Then I should see the link "Disappointed Steel"
    But I should not see the link "Random Arm"

  @terms
  Scenario: Test that a pager is shown on the solution page when needed.
    Given the following owner:
      | name                  | type                  |
      | Chiricahua Foundation | Private Individual(s) |
    And the following contact:
      | name  | Geronimo             |
      | email | geronimo@example.com |
    And the following solutions:
      | title             | description     | logo     | banner     | state     | owner                 | contact information | solution type | topic       |
      | Chiricahua Server | Serving the web | logo.png | banner.jpg | validated | Chiricahua Foundation | Geronimo            | Business      | E-inclusion |
    # There should not be a pager when the solution is empty.
    When I go to the homepage of the "Chiricahua Server" solution
    Then I should not see the "Pager" region

    # The pager should only appear when there are more than 12 items.
    Given the following distributions:
      | title           | description    | parent            |
      | Distribution 1  | Description 1  | Chiricahua Server |
      | Distribution 2  | Description 2  | Chiricahua Server |
      | Distribution 3  | Description 3  | Chiricahua Server |
      | Distribution 4  | Description 4  | Chiricahua Server |
      | Distribution 5  | Description 5  | Chiricahua Server |
      | Distribution 6  | Description 6  | Chiricahua Server |
      | Distribution 7  | Description 7  | Chiricahua Server |
      | Distribution 8  | Description 8  | Chiricahua Server |
      | Distribution 9  | Description 9  | Chiricahua Server |
      | Distribution 10 | Description 10 | Chiricahua Server |
      | Distribution 11 | Description 11 | Chiricahua Server |
      | Distribution 12 | Description 12 | Chiricahua Server |
    When I go to the homepage of the "Chiricahua Server" solution
    Then I should not see the "Pager" region
    And I should not see the "Distribution 13" tile

    # The pager cache is not invalidated when a 13th item is added.
    # https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-4235
    Given the cache has been cleared

    Given the following distributions:
      | title           | description    | parent            |
      | Distribution 13 | Description 13 | Chiricahua Server |
    When I go to the homepage of the "Chiricahua Server" solution
    Then I should see the following links:
      | Current page    |
      | Go to page 2    |
      | Go to next page |
      | Go to last page |
    But I should not see the following links:
      | Go to first page    |
      | Go to previous page |
      | Go to page 3        |

    When I click "Go to page 2"
    Then I should see the following links:
      | Go to first page    |
      | Go to previous page |
      | Go to page 1        |
      | Current page        |
    And I should see the "Distribution 13" tile
    But I should not see the following links:
      | Go to next page |
      | Go to last page |
      | Go to page 3    |
    And I should not see the "Distribution 12" tile

    # The pager being visible in one solution should not affect other solutions.
    Given the following solution:
      | title       | Chiricahua Solr       |
      | description | Searching your datas  |
      | state       | validated             |
      | owner       | Chiricahua Foundation |
    And the following distributions:
      | title               | description   | parent          |
      | Solr distribution 1 | Description 1 | Chiricahua Solr |
    When I go to the homepage of the "Chiricahua Solr" solution
    Then I should not see the "Pager" region

  @terms @javascript
  Scenario: Test that up to 7 topic terms are visible in the solution overview header.
    Given the following solutions:
      | title      | description        | logo     | banner     | state     | topic                                                                                                                              |
      | All topics | Bring in EVERYONE! | logo.png | banner.jpg | validated | Finance in EU, Supplier exchange, E-health, HR, Employment and Support Allowance, Statistics and Analysis, E-inclusion, Demography |

    When I go to the "All topics" solution
    Then I should see the text "Topic" in the "Header"
    And I should see the following links:
      | Demography                       |
      | E-health                         |
      | E-inclusion                      |
      | Employment and Support Allowance |
      | Finance in EU                    |
      | HR                               |
      | Statistics and Analysis          |
    And I should not see the link "Supplier exchange"
    When I click "HR"
    Then the url should match "/search"
    Then the option with text "- HR" from slim select "topic" is selected
    Then I should see the following facet summary "HR, Solution"
