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
