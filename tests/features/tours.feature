@api @group-f
Feature: Tours
  In order to understand complex concepts and user interfaces
  As a new user of the website
  I want to be able to follow tours that point out different sections of the page

  Scenario Outline: Anonymous user can access tours
    Given I am not logged in
    When I visit "<path>"
    Then a tour <expectation> available

    Examples:
      | path             | expectation   |
      | /                | should be     |
      | /keep-up-to-date | should be     |
      | /collections     | should not be |
      | /solutions       | should not be |
      # Recheck some URLs to ensure that cache contexts are working.
      | /                | should be     |
      | /solutions       | should not be |
      | /keep-up-to-date | should be     |

  Scenario Outline: Various user roles can access tours
    Given I am logged in as an "authenticated user"
    When I visit "<path>"

    Then a tour <expectation> available

    Examples:
      | path             | expectation   |
      | /                | should be     |
      | /keep-up-to-date | should be     |
      | /collections     | should not be |
      | /solutions       | should not be |
      # Recheck some URLs to ensure that cache contexts are working.
      | /                | should be     |
      | /solutions       | should not be |
      | /keep-up-to-date | should be     |
