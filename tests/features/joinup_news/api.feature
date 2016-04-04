@api
Feature: News API
  In order to manage custom pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "News" bundle

  Scenario: Programmatically create a News entity
    Given the following collection:
      | name              | Le Foie Heureux         |
      | owner             | Rufus Drumknott         |
      | logo              | logo.png                |
      | moderation        | 1                       |
      | closed            | 1                       |
      | elibrary creation | facilitators            |
      | uri               | http://joinup.eu/my/foo |
    And news content:
      | title      | body                                     | groups audience         |
      | Dummy News | This is some dummy content like foo:bar. | http://joinup.eu/my/foo |
    Then I should have a "News" page titled "Dummy News"