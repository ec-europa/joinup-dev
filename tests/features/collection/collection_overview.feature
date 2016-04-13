@api
Feature: Collections Overview
  Scenario: Check visibility of "Collections" menu link.
    Given I am an anonymous user
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"
    # Check that all logged in users can see and access the link as well.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"

  Scenario: View collection overview
    Given collections:
      | name              | description                    | owner | uri                                               |
      | eHealth           | Supports health-related fields |       | http://drupal.org/this/is/some/long/string-uri/id |
      | Open Data         | Facilitate access to data sets |       | http://joinup.eu/coll1                            |
      | Connecting Europe | Reusable tools and services    |       | http://joinup.eu/coll2                            |
    And users:
      | name         | role          |
      | Madame Sharn | authenticated |
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
