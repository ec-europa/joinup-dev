@api
Feature: Tours
  In order to understand complex concepts and user interfaces
  As a new user of the website
  I want to be able to follow tours that point out different sections of the page

  Scenario Outline: Anonymous user can access tours
    Given I am not logged in
    When I visit "<path>"
    Then a tour should be available

    Examples:
      | path                |
      | /                   |

  Scenario Outline: Various user roles can access tours
    Given I am logged in as a user with the "<role>" role
    When I visit "<path>"
    Then a tour should be available

    Examples:
      | path                | role          |
      | /                   | authenticated |
