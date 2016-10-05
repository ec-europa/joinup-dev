@api
Feature: Solution notification system
  In order to manage solutions
  As a user of the website
  I need to be able to receive notification on changes.

  Scenario: Test the email notification sent for solution workflow transitions.
    Given the following organisation:
      | name | The Red Search |
    And the following contact:
      | name  | Jody Buchanan            |
      | email | JodyBuchanan@example.com |
    And users:
      | name             | roles     |
      | Benjamin Stevens |           |
      | Cecelia Kim      | moderator |
    And the following solutions:
      | title                 | description           | logo     | banner     | owner          | contact information | state    |
      | The Time of the Child | The Time of the Child | logo.png | banner.jpg | The Red Search | Jody Buchanan       | proposed |
    And the following solution user memberships:
      | solution                   | user             | roles |
      | The Time of the Child      | Benjamin Stevens | owner |

    When "Cecelia Kim" changes the state of the solution "The Time of the Chile" to "Validated"
    Then "Benjamin Stevens" should receive an email