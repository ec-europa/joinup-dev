@api
Feature: Event API
  In order to manage custom pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Event" bundle

  Scenario: Programmatically create an Event entity
    Given the following collection:
      | uri               | http://joinup.eu/event_api_collection |
      | title             | Le Event Heureux                      |
      | owner             | Event Owner                           |
      | logo              | logo.png                              |
      | moderation        | yes                                   |
      | closed            | yes                                   |
      | elibrary creation | facilitators                          |
    And event content:
      | title       | field_event_short_title | body                                     | og_group_ref                          | field_start_date    |
      | Dummy Event | Short                   | This is some dummy content like foo:bar. | http://joinup.eu/event_api_collection | 2016-03-15T11:12:12 |
    Then I should have a "Event" page titled "Dummy Event"