@api @terms
Feature: Solution homepage
  In order get an idea of what a solution is about
  As a user of the website
  I need to be able see an introduction of the solution on its homepage

  # This is a regression test for the entities that include a hashmark on their Uri.
  # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3225
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
      | title             | Jira restarters                      |
      | description       | Rebooting solves all issues          |
      | documentation     | text.pdf                             |
      | elibrary creation | registered users                     |
      | landing page      | http://foo-example.com/landing       |
      | webdav creation   | no                                   |
      | webdav url        | http://joinup.eu/solution/foo/webdav |
      | wiki              | http://example.wiki/foobar/wiki      |
      | state             | validated                            |
    And news content:
      | title                             | body                             | solution        | policy domain           | spatial coverage | state     |
      | Jira will be down for maintenance | As always, during business hours | Jira restarters | Statistics and Analysis | Luxembourg       | validated |
    And custom_page content:
      | title            | body                                       | solution        |
      | Maintenance page | Jira is re-indexing. Go and drink a coffee | Jira restarters |
    When I go to the homepage of the "Jira restarters" solution
    Then I should see the "Jira will be down for maintenance" tile
    And I should not see the "Maintenance page" tile

  Scenario: A link to the first collection a solution is affiliated to should be shown in the solution header.
    Given the following solutions:
      | title       | state     |
      | Robotic arm | validated |
      | ARM9        | validated |
    And collections:
      | title              | affiliates        | state     |
      | Disappointed Steel | Robotic arm, ARM9 | validated |
      | Random Arm         | ARM9              | validated |

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
      | title             | description     | logo     | banner     | state     | owner                 | contact information | solution type     | policy domain |
      | Chiricahua Server | Serving the web | logo.png | banner.jpg | validated | Chiricahua Foundation | Geronimo            | [ABB169] Business | E-inclusion   |
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
    # https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4235
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
      | title               | description   | parent         |
      | Solr distribution 1 | Description 1 | Chiricahua Solr |
    When I go to the homepage of the "Chiricahua Solr" solution
    Then I should not see the "Pager" region
