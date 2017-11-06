@api
Feature: Tours
  In order to understand complex concepts and user interfaces
  As a new user of the website
  I want to be able to follow tours that point out different sections of the page

  # Todo remove this in ISAICP-3588
  Scenario: Tours are only available for moderators until the epic is ready
    Given I am not logged in
    And I am on the homepage
    Then a tour should not be available
    Given I am logged in as a user with the "authenticated" role
    And I am on the homepage
    Then a tour should not be available

  Scenario Outline: Anonymous user can access public pages
    Given I am logged in as a user with the "moderator" role
    When I visit "<path>"
    Then a tour should be available

    Examples:
      | path                |
      | /                   |
      | collections         |
