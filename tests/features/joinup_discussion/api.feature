@api
Feature: Discussion API
  In order to manage discussion entities programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Discussion" bundle

  Scenario: Programmatically create a discussion
    Given discussion content:
      | title                | content                  |
      | Discussion API title | Let us have a discussion |
    Then I should have a "Discussion" page titled "Discussion API title"
