@api
Feature: Collections Overview
  Scenario: View collection overview
    Given collections:
      | name              | description                    | author          | uri                        |
      | eHealth           | Supports health-related fields | Arnold Sideways | http://drupal.org/notfound |
      | Open Data         | Facilitate access to data sets | Madame Sharn    | http://joinup.eu/coll1     |
      | Connecting Europe | Reusable tools and services    | Ptaclusp IIb    | http://joinup.eu/coll2     |
    And The collections are indexed
    When I visit the collection overview page
    Then I should see the link "eHealth"
    And I should see the text "Supports health-related fields"
    And I should see the link "Open Data"
    And I should see the text "Facilitate access to data sets"
    And I should see the link "Connecting Europe"
    And I should see the text "Reusable tools and services"