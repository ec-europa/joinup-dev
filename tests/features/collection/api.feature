Feature: Collection API
  In order to manage collections programmatically
  As a backend developer
  I need to be able to use the Collection API

  Scenario: Programmatically create a collection
    Given the following collection:
      | name              | Open Data Initiative        |
      | logo              | logo.png                    |
      | moderation        | 0                           |
      | closed            | 0                           |
      | elibrary creation | facilitators                |
      | uri               | https://ec.europa.eu/my/url |
    Then I should have 1 collection

  Scenario: Programmatically create a collection using only the name
    Given the following collection:
      | name            | EU Interoperability Support Group |
      | uri             | http://joinup.eu/collection/eisg  |
    Then I should have 1 collection
