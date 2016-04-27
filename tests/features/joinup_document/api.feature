@api
Feature: Document API
  In order to manage document entities programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Documents" bundle

  Scenario: Programmatically create a Custom Page
    Given the following collection:
      | title             | Joinup document name |
      | owner             | Joinup Derp          |
      | logo              | logo.png             |
      | moderation        | yes                  |
      | closed            | yes                  |
      | elibrary creation | facilitators         |
    And document content:
      | title    | field_document_short_title | body               | collection              |
      | JD title | Short                      | Dummy description. | http://joinup.eu/jd/jde |
    Then I should have a "Document" page titled "JD title"