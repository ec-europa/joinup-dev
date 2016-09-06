@api
Feature: "Custom page" search.
  In order to use the search
  As a user of the website
  I need to be able to view search results in the "Custom page" canonical page.

  Background:
    Given the following collection:
      | title  | Code camp  |
      | logo   | logo.png   |
      | banner | banner.jpg |
    And discussion content:
      | title              | content                        |
      | Discussion example | This is some dummy discussion. |
    And "custom_page" content:
      | title    | collection | body               |
      | About us | Code camp  | This is an example |
    # Non UATable step.
    Given I commit the solr index

    Scenario: View search results in the "Custom page" canonical page.
      When I am an anonymous user
      And I go to the homepage of the "Code camp" collection
      And I click "About us"
      Then I should see the heading "Discussion example"

    Scenario: Editing of the search field.
      When I am logged in as a facilitator of the "Code camp" collection
      And I go to the "About us" custom page
      And I click "Edit"
      Then I should see the heading "Edit Custom page About us"
      And the following fields should not be present "Query presets, Limit"

      When I am logged in as a "moderator"
      And I go to the "About us" custom page
      And I click "Edit"
      Then I should see the heading "Edit Custom page About us"
      And the following fields should be present "Query presets, Limit"
