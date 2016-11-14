@api @email
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
    Then the following email should have been sent:
      | template  | Message to the owner when a solution transits to 'Validated' state           |
      | recipient | Benjamin Stevens                                                             |
      | subject   | Joinup - Content has been updated                                            |
      | body      | The content "The Time of the Child" has been moved to the "Validated" state. |

    # Test deletion request email.
    When the user "Benjamin Stevens" changes the state of the "The Time of the Child" solution to "Request deletion"
    Then the following email should have been sent:
      | template  | Message to the moderator when a request for deletion is made on a solution                           |
      | recipient | Cecelia Kim                                                                                          |
      | subject   | Joinup - A request for deletion has been made                                                        |
      | body      | The owner of the "The Time of the Child" solution has requested that the solution should be deleted. |

    # Test deletion email.
    When I am logged in as "Cecelia Kim"
    And I go to the homepage of the "Some Scent" solution
    And I click Edit
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | template  | Message to the owner when a solution is deleted        |
      | recipient | Benjamin Stevens                                       |
      | subject   | Joinup - Content has been deleted                      |
      | body      | The content "Some Scent" has been deleted from Joinup. |
