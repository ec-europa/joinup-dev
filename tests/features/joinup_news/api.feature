@api
Feature: News API
  In order to manage custom pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "News" bundle

  Scenario: Programmatically create a News entity
    Given the following owner:
      | name            |
      | Rufus Drumknott |
    And the following collection:
      | title             | Le Foie Heureux |
      | owner             | Rufus Drumknott |
      | logo              | logo.png        |
      | moderation        | yes             |
      | elibrary creation | facilitators    |
      | state             | validated       |
    And news content:
      | title      | body                                     | collection      |
      | Dummy News | This is some dummy content like foo:bar. | Le Foie Heureux |
    Then I should have a "News" page titled "Dummy News"
