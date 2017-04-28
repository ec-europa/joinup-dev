@api
Feature: User profile
  A user must be able to change his own profile.
  A moderator must be able to edit any user account.

  @terms
  Scenario: A logged-in user can navigate to his own profile and edit it.
    Given users:
      | Username             | E-mail              |
      | Leonardo Da Vinci    | foo@bar.com         |
      | Domenico Ghirlandaio | domedome@firenze.it |
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
    And I should see the text "Supplier exchange"
    # @todo The nationality will be rendered as flag image.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3175
    # And I should see the link "Italy"
    # A user should not be able to edit the profile page of another user.
    When I go to the public profile of "Domenico Ghirlandaio"
    Then I should not see the link "Edit"

  @terms
  Scenario: A moderator can navigate to any users profile and edit it.
    Given users:
      | Username          | E-mail                | Roles     |
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

  # Regression test: the wrong profile picture was showing due to a caching problem.
  Scenario: The user's profile picture should be shown in the page header.
    Given users:
      | Username          | E-mail                | Photo        |
      | Leonardo Da Vinci | leonardo@example.com  | leonardo.jpg |
      | Ada Lovelace      | moderator@example.com | ada.png      |

    When I am logged in as "Leonardo Da Vinci"
    Then my user profile picture should be shown in the page header
    When I am logged in as "Ada Lovelace"
    Then my user profile picture should be shown in the page header

  Scenario: The user public profile page shows the content he's author of or is member of.
    Given users:
      | Username          | E-mail                        | First name | Family name |
      | Corwin Robert     | corwin.robert@example.com     |            |             |
      | Anise Edwardson   | anise.edwardson@example.com   |            |             |
      | Jayson Granger    | jayson.granger@example.com    |            |             |
      | Clarette Fairburn | clarette.fairburn@example.com | Clarette   | Fairburn    |
    And the following solutions:
      | title              | description                                     | logo     | banner     | state     |
      | E.C.O. fertilizers | Ecologic cool organic fertilizers production.   | logo.png | banner.jpg | validated |
      | SOUND project      | Music playlist for growing flowers with rhythm. | logo.png | banner.jpg | validated |
    And the following collections:
      | title                 | description                           | logo     | banner     | state     | affiliates         |
      | Botanic E.D.E.N.      | European Deep Earth Nurturing project | logo.png | banner.jpg | validated | E.C.O. fertilizers |
      | Ethic flower handling | Because even flowers have feelings.   | logo.png | banner.jpg | validated | SOUND project      |
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
    And discussion content:
      | title                  | author          | collection            | state     |
      | Repopulating blue iris | Corwin Robert   | Botanic E.D.E.N.      | validated |
      | Flowers are people too | Anise Edwardson | Ethic flower handling | validated |

    When I am an anonymous user
    And I go to the public profile of "Corwin Robert"
    Then I should see the heading "Corwin Robert"
    # Tiles should be shown for the groups the user is member of or author of.
    And I should see the "Botanic E.D.E.N." tile
    And I should see the "SOUND project" tile
    And I should see the "Repopulating blue iris" tile

    But I should not see the "Ethic flower handling" tile
    But I should not see the "Flowers are people too" tile
    And I should not see the "E.C.O. fertilizers" tile
    # Contact information and owner tiles should never be shown.
    And I should not see the "Wibo Verhoeven" tile
    And I should not see the "Somboon De Laurentis" tile

    # A message should be shown when visiting a profile of a user without
    # content.
    When I go to the public profile of "Clarette Fairburn"
    Then I should see the text "Clarette does not have any content yet."

    When I go to the public profile of "Jayson Granger"
    # This user has no first name inserted, so the message is generic.
    Then I should see the text "This user does not have any content yet."

  Scenario: The user profile page title should show the full name of the user.
    Given users:
      | Username   | E-mail                       | First name | Family name |
      | cgarnett67 | callista.garnett@example.com | Callista   | Garnett     |
      | delwin999  | deforest.elwin@example.com   |            |             |

    # When the user has filled first and family name, the profile should show
    # the full name as header title and in the page title tag.
    When I go to the public profile of cgarnett67
    Then I should see the heading "Callista Garnett" in the "Header" region
    And the HTML title tag should contain the text "Callista Garnett"
    # The title should not be duplicated.
    And I should not see the "Page title" region
    And I should not see the heading "cgarnett67"

    # The full name fall backs to the user name when the fields are not filled.
    When I go to the public profile of delwin999
    Then I should see the heading delwin999 in the "Header" region
    And the HTML title tag should contain the text delwin999
    And I should not see the "Page title" region
