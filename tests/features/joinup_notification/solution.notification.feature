@api @wip
Feature: Solution notification system
  In order to manage solutions
  As a user of the website
  I need to be able to receive notification on changes.

  Scenario: Test the email notification sent for solution workflow transitions.
    Given the following owner:
      | name           | type                          |
      | The Red Search | Non-Governmental Organisation |
    And the following contact:
      | name  | Jody Buchanan            |
      | email | JodyBuchanan@example.com |
    And users:
      | Username         | E-mail                      | Roles     |
      | Benjamin Stevens | BenjaminStevens@example.com |           |
      | Cecelia Kim      | CeceliaKim@example.com      | moderator |
    And the following solutions:
      | title                 | description           | logo     | banner     | owner          | contact information | state            |
      | The Time of the Child | The Time of the Child | logo.png | banner.jpg | The Red Search | Jody Buchanan       | proposed         |
    And the following solution user memberships:
      | solution              | user             | roles |
      | The Time of the Child | Benjamin Stevens | owner |

    # Test validation email.
    Given I am logged in as "Cecelia Kim"
    When I go to the homepage of the "The Time of the Child" solution
    And I click Edit
    When I press "Publish"
    Then the following email should have been sent:
      | template  | Message to the owner when a solution transits to 'Validated' state           |
      | recipient | Benjamin Stevens                                                             |
      | subject   | Joinup - Content has been updated                                            |
      | body      | The content "The Time of the Child" has been moved to the "Validated" state. |
