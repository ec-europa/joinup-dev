Feature: Contact information access
  In order to see contextualized information
  As a visitor
  I don't want to see the contact information full page

  Scenario: Anonymous user contact information access
    Given the following contact:
      | email       | phil.coulson@shield.gov |
      | name        | Phil Coulson            |
      | Website URL | http://shield.gov       |
    When I am an anonymous user
    When I go to the "Phil Coulson" contact information page
    Then I should see the text "Access denied. You must log in to view this page. "
