@api
Feature: Owner access
  In order to see contextualized information
  As a visitor
  I don't want to see the owner full page.

  Scenario: Anonymous user contact information access
    Given owner:
      | name          | type                  |
      | Daisy Johnson | Private Individual(s) |
    When I am an anonymous user
    When I go to the "Daisy Johnson" owner
    Then I should see the text "Access denied. You must sign in to view this page. "
