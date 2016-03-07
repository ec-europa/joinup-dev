@api
Feature: Collections Overview
  Scenario: View collection overview
    Given collections:
      | name              | description                    | owner           | uri                        |
      | eHealth           | Supports health-related fields |                 | http://drupal.org/notfound |
      | Open Data         | Facilitate access to data sets |                 | http://joinup.eu/coll1     |
      | Connecting Europe | Reusable tools and services    |                 | http://joinup.eu/coll2     |
    And users:
      | name           | role          |
      | Madame Sharn   | authenticated |
    And I am logged in as "Madame Sharn"
    When I visit the collection overview page
    Then I should see the link "eHealth"
    And I should see the text "Supports health-related fields"
    And I should see the link "Open Data"
    And I should see the text "Facilitate access to data sets"
    And I should see the link "Connecting Europe"
    And I should see the text "Reusable tools and services"
    When I click "eHealth"
    Then I should see the heading "eHealth"

