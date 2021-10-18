@api @group-f
Feature: Custom page API
  In order to manage custom pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Custom page" bundle

  Scenario: Programmatically create a Custom page
    Given the following owner:
      | name            |
      | Rufus Drumknott |
    And the following collection:
      | title            | Le Foie Heureux          |
      | owner            | Rufus Drumknott          |
      | logo             | logo.png                 |
      | moderation       | yes                      |
      | content creation | facilitators and authors |
      | state            | validated                |
    And custom_page content:
      | title      | body                                     | collection      |
      | Dummy page | This is some dummy content like foo:bar. | Le Foie Heureux |
     # @Fixme unimplemented. See ISAICP-2369
     # | Exclude from menu |                                              |
    Then I should have a "Custom page" page titled "Dummy page"
