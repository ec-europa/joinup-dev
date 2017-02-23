@api
Feature: User profile
  A user must be able to change his own profile.
  A moderator must be able to edit any user account.

  @terms
  Scenario: A logged-in user can navigate to his own profile and edit it.
    Given users:
      | name              | mail        | roles |
      | Leonardo Da Vinci | foo@bar.com |       |
    When I am logged in as "Leonardo Da Vinci"
    And I am on the homepage
    Then I click "My account"
    Then I click "Edit"
    Then the following fields should be present "Current password, Email address, Password, Confirm password, First name"
    Then the following fields should be present "Family name, Photo, Nationality, Professional domain"
    Then the following fields should not be present "Time zone"
    And I fill in "First name" with "Leoke"
    And I fill in "Family name" with "di ser Piero da Vinci"
    And I fill in "Professional domain" with "Supplier exchange"
    And I fill in "Nationality" with "Italy"
    And I press the "Save" button
    Then I should see the success message "The changes have been saved."
    Then I click 'View'
    And I should see the text "Leoke"
    And I should see the text "di ser Piero da Vinci"
    And I should see the link "Supplier exchange"
    And I should see the link "Italy"

  @terms
  Scenario: A moderator can navigate to any users profile and edit it.
    Given users:
      | name              | mail                  | roles     |
      | Leonardo Da Vinci | leonardo@example.com  |           |
      | Mighty mod        | moderator@example.com | moderator |
    When I am logged in as "Mighty mod"
    And I go to the homepage
    Then I click "People"
    Then I should be on "admin/people"
    Then I fill in "Name or email contains" with "Leonardo"
    And I press the "Filter" button
    Then I click "Leonardo Da Vinci"
    Then I click "Edit"
    Then the following fields should be present "Email address, Username, Password, Confirm password"
    Then the following fields should be present "First name, Family name, Photo, Professional domain, Business title"
    Then the following fields should be present "Nationality, Professional domain, Organisation"
    Then the following fields should not be present "Time zone"
    And I fill in "First name" with "Leo"
    And I fill in "Family name" with "di ser Piero da Vinci"
    And I fill in "Professional domain" with "Finance in EU"
    And I fill in "Nationality" with "Italy"
    And I press the "Save" button
    Then I should see the success message "The changes have been saved."
    # This message is typical shown when the mail server is not responding. This is just a smoke test
    # to see that all is fine and dandy, and mails are being delivered.
    Then I should not see the error message "Unable to send email. Contact the site administrator if the problem persists."

  Scenario: The user public profile page shows the content he's author of or is member of.
    Given users:
      | name            | mail                        |
      | Corwin Robert   | corwin.robert@example.com   |
      | Anise Edwardson | anise.edwardson@example.com |
    And the following solutions:
      | title              | description                                     | logo     | banner     | state     |
      | E.C.O. fertilizers | Ecologic cool organic fertilizers production.   | logo.png | banner.jpg | validated |
      | SOUND project      | Music playlist for growing flowers with rhythm. | logo.png | banner.jpg | validated |
    And the following collections:
      | title                 | description                           | logo     | banner     | state     | affiliates         |
      | Botanic E.D.E.N.      | European Deep Earth Nurturing project | logo.png | banner.jpg | validated | E.C.O. fertilizers |
      | Ethic flower handling | Because even flowers have feelings.   | logo.png | banner.jpg | validated | SOUND project      |
    And discussion content:
      | title                  | author          | collection            | state     |
      | Repopulating blue iris | Corwin Robert   | Botanic E.D.E.N.      | validated |
      | title                  | Anise Edwardson | Ethic flower handling | validated |
    And the following contact:
      | name        | Wibo Verhoeven             |
      | email       | wibo.verhoeven@example.com |
      | Website URL | http://example.com         |
      | author      | Corwin Robert              |
    And the following owner:
      | type                  | name                 | author        |
      | Private Individual(s) | Somboon De Laurentis | Corwin Robert |
    And the following collection user membership:
      | user          | collection       |
      | Corwin Robert | Botanic E.D.E.N. |
    And the following solution user membership:
      | user          | solution      |
      | Corwin Robert | SOUND project |

    When I am an anonymous user
    And I go to the public profile of "Corwin Robert"
    Then I should see the heading "Corwin Robert"
    # Tiles should be shown for the groups the user is member of or author of.
    And I should see the "Botanic E.D.E.N." tile
    And I should see the "SOUND project" tile
    And I should see the "Repopulating blue iris" tile

    But I should not see the "Ethic flower handling" tile
    And I should not see the "E.C.O. fertilizers" tile
    # Contact information and owner tiles should never be shown.
    And I should not see the "Wibo Verhoeven" tile
    And I should not see the "Somboon De Laurentis" tile
