@api
Feature: Document API
  In order to manage document entities programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Documents" bundle

  Scenario: Programmatically create a document
    Given the following owner:
      | name        |
      | Joinup Derp |
    And the following collection:
      | title             | Joinup document name |
      | owner             | Joinup Derp          |
      | logo              | logo.png             |
      | moderation        | yes                  |
      | elibrary creation | facilitators         |
      | state             | validated            |
    And document content:
      | title    | type     | short title | body               | collection           |
      | JD title | Document | Short       | Dummy description. | Joinup document name |
    Then I should have a "Document" page titled "JD title"
