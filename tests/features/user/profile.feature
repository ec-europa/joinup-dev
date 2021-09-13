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
    # If no first name and last name are given, the title should default to the
    # username.
    Then I should see the heading "Leonardo Da Vinci"
    And I should see the avatar "user_icon.png"

    But I should not see the text "Country of origin:" in the "Header" region
    And I should not see the link "View" in the "Entity actions" region

    When I click "Edit"
    Then the following fields should be present "Current password, Email, Password, Confirm password, First name"
    And the following fields should be present "Family name, Photo, Country of origin, Professional domain, Business title"
    And the following fields should be present "Facebook, Twitter, LinkedIn, GitHub, SlideShare, Youtube, Vimeo"
    And the following fields should not be present "Time zone"
    # Username label and user name are on separate lines to be more MDL-like after ISAICP-3770
    And I should see the text "Username"
    And I should see the text "Leonardo Da Vinci"
    And I fill in "First name" with "Leoke"
    And I fill in "Family name" with "di ser Piero da Vinci"
    And I select "Supplier exchange" from "Professional domain"
    And I fill in "Country of origin" with "Italy"
    And I fill in "Facebook" with "leodavinci"
    And I fill in "Twitter" with "therealdavinci"
    And I fill in "LinkedIn" with "leonardo.davinci"
    And I fill in "GitHub" with "davinci"

    # Verify that the business title is a field limited to 255 characters.
    When I fill in "Business title" with "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent sagittis justo ornare justo porta tristique vitae eu ligula. Mauris iaculis eros id nulla posuere, id luctus orci ultricies. Aenean at leo diam. Aliquam dapibus nibh et est pharetra, quis interdum"
    And I press the "Save" button
    Then I should see the error message "Business title cannot be longer than 255 characters but is currently 262 characters long."

    When I fill in "Business title" with "Italian Renaissance polymath"
    And I press the "Save" button
    Then I should see the success message "The changes have been saved."
    And I should see the heading "Leoke di ser Piero da Vinci" in the "Header" region
    And I see the text "Italian Renaissance polymath" in the "Header" region
    And I should see the text "Supplier exchange"
    And I should see the link "Edit"
    And the link "Facebook" in the "Header" region should point to "https://www.facebook.com/leodavinci"
    And the link "Twitter" in the "Header" region should point to "https://www.twitter.com/therealdavinci"
    And the link "LinkedIn" in the "Header" region should point to "https://www.linkedin.com/leonardo.davinci"
    And the link "GitHub" in the "Header" region should point to "https://github.com/davinci"
    And I should not see the link "SlideShare" in the "Header" region
    And I should not see the link "Youtube" in the "Header" region
    And I should not see the link "Vimeo" in the "Header" region
    # @todo The nationality will be rendered as flag image.
    # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3175
    # And I should see the link "Italy"
    # A user should not be able to edit the profile page of another user.
    When I go to the public profile of "Domenico Ghirlandaio"
    Then I should not see the link "Edit"
    # Verify that the user's "Country of origin" field is visible on its profile.
    When I go to the public profile of "Leonardo Da Vinci"
    Then I should see the text "Country of origin: Italy" in the "Header" region

  @terms @email
  Scenario: A moderator can navigate to any user's profile and edit it.
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
    Then the following fields should be present "Email, Username, Password, Confirm password"
    And the following fields should be present "First name, Family name, Photo, Professional domain, Business title"
    And the following fields should be present "Country of origin, Organisation"
    And the following fields should not be present "Time zone"
    And I should not see the text "Username: Leonardo Da Vinci"
    And I fill in "First name" with "Leo"
    And I fill in "Family name" with "di ser Piero da Vinci"
    And I select "Finance in EU" from "Professional domain"
    And I fill in "Country of origin" with "Italy"
    And I press the "Save" button
    Then I should see the success message "The changes have been saved."
    And I should see the success message "The user has been notified that their account has been updated."
    And the following email should have been sent:
      | recipient | Leonardo Da Vinci                                                                                         |
      | subject   | The Joinup Support Team updated your account for you at Joinup                                            |
      | body      | A moderator has edited your user profile on Joinup. Please check your profile to verify the changes done. |

  # Regression test: the wrong profile picture was showing due to a caching problem.
  Scenario: The user's profile picture should be shown in the page header.
    Given users:
      | Username          | E-mail                | Photo        |
      | Leonardo Da Vinci | leonardo@example.com  | leonardo.jpg |
      | Ada Lovelace      | moderator@example.com | ada.png      |

    When I am logged in as "Leonardo Da Vinci"
    # New homepage has a different header where the profile picture is no longer
    # shown. Let's move to a page that still shows the old header.
    And I visit the collection overview
    Then my user profile picture should be shown in the page header
    When I am logged in as "Ada Lovelace"
    And I visit the collection overview
    Then my user profile picture should be shown in the page header

  Scenario: The user public profile page shows the content he's author of or is member of.
    Given users:
      | Username          | E-mail                        | First name | Family name |
      | Corwin Robert     | corwin.robert@example.com     |            |             |
      | Anise Edwardson   | anise.edwardson@example.com   |            |             |
      | Jayson Granger    | jayson.granger@example.com    |            |             |
      | Clarette Fairburn | clarette.fairburn@example.com | Clarette   | Fairburn    |
    And the following collections:
      | title                 | description                           | logo     | banner     | state     | creation date    |
      | Botanic E.D.E.N.      | European Deep Earth Nurturing project | logo.png | banner.jpg | validated | 2017-02-23 10:00 |
      | Ethic flower handling | Because even flowers have feelings.   | logo.png | banner.jpg | validated | 2017-02-23 12:00 |
    And the following solutions:
      | title              | collection            | description                                     | logo     | banner     | state     | creation date    |
      | E.C.O. fertilizers | Botanic E.D.E.N.      | Ecologic cool organic fertilizers production.   | logo.png | banner.jpg | validated | 2017-02-23 13:00 |
      | SOUND project      | Ethic flower handling | Music playlist for growing flowers with rhythm. | logo.png | banner.jpg | validated | 2017-02-23 14:01 |
    And discussion content:
      | title                          | author          | collection            | state     | created          |
      | Repopulating blue iris         | Corwin Robert   | Botanic E.D.E.N.      | validated | 2018-06-15 16:00 |
      | Best topsoil for plant comfort | Anise Edwardson | Ethic flower handling | validated | 2018-09-01 19:30 |
    And document content:
      | title                    | author        | collection       | state     | created          |
      | Cherry blossoms schedule | Corwin Robert | Botanic E.D.E.N. | validated | 2017-05-13 16:00 |
    And event content:
      | title                | author        | collection       | state     | created          |
      | Spring blossom party | Corwin Robert | Botanic E.D.E.N. | validated | 2018-06-27 18:00 |
    And news content:
      | title                         | author        | collection       | state     | created         |
      | Discovered new flower species | Corwin Robert | Botanic E.D.E.N. | validated | 2018-11-15 9:01 |
    And video content:
      | title                 | author        | collection       | state     | created         |
      | Planting a tree howto | Corwin Robert | Botanic E.D.E.N. | validated | 2017-10-30 9:30 |
    # Contact information and owner tiles should never be shown.
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
    Then I should see the following tiles in the correct order:
      | Discovered new flower species |
      | Spring blossom party          |
      | Repopulating blue iris        |
      | Planting a tree howto         |
      | Cherry blossoms schedule      |
      | SOUND project                 |
      | Botanic E.D.E.N.              |

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
    And the HTML title of the page should be "Callista Garnett"
    # The title should not be duplicated.
    And I should not see the "Page title" region
    And I should not see the heading "cgarnett67"

    # The full name fall backs to the user name when the fields are not filled.
    When I go to the public profile of delwin999
    Then I should see the heading delwin999 in the "Header" region
    And the HTML title of the page should be delwin999
    And I should not see the "Page title" region

  Scenario: The user profile page is updated when the user joins or leaves a collection
    Given users:
      | Username      | E-mail                           |
      | Korben Dallas | k.dallas@cabs.services.zorg.corp |
    And collection:
      | title | Federated Army Veterans |
      | state | validated               |
    # Visit the user profile page for the first time. It should not yet be
    # cached and we should not see anything about the collection since we are
    # not a member.
    When I am an anonymous user
    And I go to the public profile of "Korben Dallas"
    Then I should not see the "Federated Army Veterans" tile
    And the page should not be cached

    # On the next visit the page should be cached.
    When I reload the page
    Then the page should be cached

    # Join the collection. Now the cache of the user profile page should be
    # cleared and the collection that was joined should show up.
    Given I am logged in as "Korben Dallas"
    And I go to the homepage of the "Federated Army Veterans" collection
    And I press the "Join this collection" button

    When I am an anonymous user
    And I go to the public profile of "Korben Dallas"
    Then I should see the "Federated Army Veterans" tile
    And the page should not be cached

    # Verify the page can be cached correctly.
    When I reload the page
    Then the page should be cached

    # Leave the collection. Now the cache of the user profile page should be
    # cleared and the collection that was left should no longer show up.
    Given I am logged in as "Korben Dallas"
    And I go to the homepage of the "Federated Army Veterans" collection
    And I click "Leave this collection"
    And I press the "Confirm" button

    When I am an anonymous user
    And I go to the public profile of "Korben Dallas"
    Then I should not see the "Federated Army Veterans" tile
    And the page should not be cached

    # Verify the page can be cached correctly.
    When I reload the page
    Then the page should be cached

  Scenario: An authenticated user should not have access to restricted pages of his profile.
    When I am logged in as an "authenticated user"
    And I am on the homepage
    And I click "My account"
    Then I should not see the link "Subscription settings"
    And I should not see the link "Persistent Logins"

  @email
  Scenario: A user, changing its E-mail should receive a notification on his old
  E-mail address and a verification link on its new address.

    Given users:
      | Username       | E-mail         | Password | First name | Family name |
      | Caitlyn Jenner | he@example.com | secret   | Caitlyn    | Jenner      |
    When I am logged in as "Caitlyn Jenner"
    And I am on the homepage
    And I click "My account"
    When I click "Edit"
    Then the "Email" field should contain "he@example.com"

    Given I fill in "Current password" with "secret"
    And I fill in "Email" with "she@example.com"
    When I press "Save"
    Then I should see the following warning messages:
      | warning messages                                                                                                 |
      | Your updated email address needs to be validated. Further instructions have been sent to your new email address. |
    And the following email should have been sent:
      | recipient_mail | he@example.com                                                                                                          |
      | subject        | Joinup: Email change information for Caitlyn Jenner                                                                     |
      | body           | In order to complete the change you will need to follow the instructions sent to your new email address within one day. |
    And the following email should have been sent:
      | recipient_mail | she@example.com                                                                                                                   |
      | subject        | Joinup: Email change information for Caitlyn Jenner                                                                               |
      | body           | A request to change your email address has been made in your Joinup profile. To confirm the request, please click the link below: |

    But I click the mail change link from the email sent to "she@example.com"
    Then I should see the following success messages:
      | success messages                                        |
      | Your email address has been changed to she@example.com. |
    # Check that the E-mail has been successfully updated.
    When I click "My account"
    And I click "Edit"
    Then the "Email" field should contain "she@example.com"
