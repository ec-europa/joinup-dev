@api
Feature: Document API
  In order to manage document entities programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Documents" bundle

  Scenario: Programmatically create a Custom Page
    Given the following collection:
      | name              | Joinup document name    |
      | owner             | Joinup Derp             |
      | logo              | logo.png                |
      | moderation        | 1                       |
      | closed            | 1                       |
      | elibrary creation | facilitators            |
      | uri               | http://joinup.eu/jd/jde |
    And document content:
      | title      | field_document_short_title | body               | groups audience         |
      | JD title   | Short                      | Dummy description. | http://joinup.eu/jd/jde |
    Then I should have a "Document" page titled "JD title"