@api
Feature: Custom page API
  In order to manage custom pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Custom page" bundle

  Scenario: Programmatically create a Custom page
    Given the following collection:
      | uri               | http://joinup.eu/custom_page_api_collection |
      | title             | Le Foie Heureux                             |
      | owner             | Rufus Drumknott                             |
      | logo              | logo.png                                    |
      | moderation        | yes                                         |
      | closed            | yes                                         |
      | elibrary creation | facilitators                                |
    And custom_page content:
      | title      | body                                     | groups audience                             |
      | Dummy page | This is some dummy content like foo:bar. | http://joinup.eu/custom_page_api_collection |
     # @Fixme unimplemented. See ISAICP-2369
     # | Exclude from menu |                                              |
    Then I should have a "Custom page" page titled "Dummy page"
