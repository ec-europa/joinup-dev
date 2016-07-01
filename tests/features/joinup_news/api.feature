@api
Feature: News API
  In order to manage custom pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "News" bundle

  Scenario: Programmatically create a News entity
    Given the following collection:
      | uri               | http://joinup.eu/news_api_collection |
      | title             | Le Foie Heureux                      |
      | owner             | Rufus Drumknott                      |
      | logo              | logo.png                             |
      | moderation        | yes                                  |
      | closed            | yes                                  |
      | elibrary creation | facilitators                         |
    And news content:
      | title      | body                                     | og_group_ref                         |
      | Dummy News | This is some dummy content like foo:bar. | http://joinup.eu/news_api_collection |
    Then I should have a "News" page titled "Dummy News"