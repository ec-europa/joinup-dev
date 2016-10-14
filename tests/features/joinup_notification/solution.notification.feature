@api @email
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
      | name             | mail                        | roles     |
      | Benjamin Stevens | BenjaminStevens@example.com |           |
      | Cecelia Kim      | CeceliaKim@example.com      | moderator |
    And the following solutions:
      | title                 | description           | logo     | banner     | owner          | contact information | state            |
      | The Time of the Child | The Time of the Child | logo.png | banner.jpg | The Red Search | Jody Buchanan       | proposed         |
      | Some Scent            | Some Scent            | logo.png | banner.jpg | The Red Search | Jody Buchanan       | deletion_request |
    And the following solution user memberships:
      | solution              | user             | roles |
      | The Time of the Child | Benjamin Stevens | owner |
      | Some Scent            | Benjamin Stevens | owner |

    # Test validation email.
    When the user "Cecelia Kim" changes the state of the "The Time of the Child" solution to "Validated"
    Then an email should be sent to "Benjamin Stevens"
    # Test deletion email.
    When I am logged in as "Cecelia Kim"
    And I go to the homepage of the "Some Scent" solution
    And I click Edit
    And I click "Delete"
    And I press "Delete"
    Then an email should be sent to "Benjamin Stevens"
