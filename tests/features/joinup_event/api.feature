@api
Feature: Event API
  In order to manage custom pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Event" bundle

  Scenario: Programmatically create an Event entity
    Given the following collection:
      | name              | Le Event Heureux        |
      | owner             | Event Owner             |
      | logo              | logo.png                |
      | moderation        | 1                       |
      | closed            | 1                       |
      | elibrary creation | facilitators            |
      | uri               | http://joinup.eu/event  |
    And joinup_event content:
      | title       | short_title | body                                     | groups audience         |
      | Dummy Event | Short       | This is some dummy content like foo:bar. | http://joinup.eu/event  |
    Then I should have a "Event" page titled "Dummy Event"