Feature: Collection API
  In order to manage collections programmatically
  As a backend developer
  I need to be able to use the Collection API

  Scenario: Programmatically create a collection
    Given the following collection:
      | title             | Open Data Initiative |
      | logo              | logo.png             |
      | moderation        | no                   |
      | elibrary creation | facilitators         |
    Then I should have 1 collection

  Scenario: Programmatically create a collection using only the name
    Given the following collection:
      | title | EU Interoperability Support Group |
    Then I should have 1 collection
