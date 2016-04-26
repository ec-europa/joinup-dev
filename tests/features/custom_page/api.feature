@api
Feature: Custom page API
  In order to manage custom pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Custom page" bundle

  Scenario: Programmatically create a Custom page
    Given the following collection:
      | name              | Le Foie Heureux         |
      | owner             | Rufus Drumknott         |
      | logo              | logo.png                |
      | moderation        | 1                       |
      | closed            | 1                       |
      | elibrary creation | facilitators            |
      | uri               | http://joinup.eu/my/foo |
    And custom_page content:
      | title      | body                                     | groups audience         |
      | Dummy page | This is some dummy content like foo:bar. | http://joinup.eu/my/foo |
     # @Fixme unimplemented. See ISAICP-2369
     # | Exclude from menu |                                              |
     Then I should have a "Custom page" page titled "Dummy page"
