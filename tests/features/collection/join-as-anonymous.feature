@api @group-c
Feature: Joining a collection as an anonymous user
  In order to participate in the activities of a collection
  As an anonymous user
  I need to be able to join a collection after authenticating

  Background:
    Given collections:
      | title           | abstract                          | closed | state     |
      | Reannual plants | Harvested before they are planted | no     | validated |
      | Vul nuts        | Used to brew Ghlen Livid          | yes    | validated |

  @javascript
  Scenario: Anonymous users can join a collection after creating an account
    And CAS users:
      | Username           | E-mail         | Password |
      | Iodine Maccalariat | iodine@ankh.am | 10d1ne   |
    And the following legal document version:
      | Document     | Label | Published | Acceptance label                                                                                   | Content                                                    |
      | Legal notice | 1.1   | yes       | I have read and accept the <a href="[entity_legal_document:url]">[entity_legal_document:label]</a> | The information on this site is subject to a disclaimer... |

    # Anonymous users should be able to join a collection but not leave one.
    Given I am an anonymous user
    When I go to the homepage of the "Reannual plants" collection
    # These are links which are styled as buttons.
    Then I should see the link "Join this collection"
    But I should not see the link "Leave this collection"

    When I click "Join this collection"
    Then I should see the text "Sign in to join"
    And I should see "Only signed in users can join this collection. Please sign in or register an account on EU Login."
    But the cookie that tracks which group I want to join should not be set
    When I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"
    And a cookie should be set that allows me to join "Reannual plants" after authenticating

    When I fill in "E-mail address" with "iodine@ankh.am"
    And I fill in "Password" with "10d1ne"
    And I press "Log in"
    And I select the radio button "I am a new user (create a new account)"
    And I check the "I have read and accept the Legal notice" material checkbox
    And I press "Next"

    # The user should be redirected to the collection so they can opt in to
    # receiving notifications.
    Then I should see the heading "Reannual plants"
    And I should see the success message "You have been logged in."
    And I should see the success message "You are now a member of Reannual plants."
    And the "Reannual plants" collection should have 1 active member
    And a modal should open
    # Quick check without pressing buttons, this is fully tested in the
    # subscribe-on-join scenario.
    And I should see the text "Welcome to Reannual plants" in the "Modal title"
    And I should see the text "Want to receive notifications, too?" in the "Modal content"
    And I should see the button "No thanks" in the "Modal buttons" region
    And I should see the button "Subscribe" in the "Modal buttons" region

    # The modal should not open when visiting the page for a second time.
    When I reload the page
    And I wait for AJAX to finish
    Then I should not see the text "Welcome to Reannual plants"

    # Now that the user is a member, if we log out and try to join again as an
    # anonymous user, we should be shown an appropriate message.
    Given I am an anonymous user
    When I go to the homepage of the "Reannual plants" collection
    And I click "Join this collection"
    And I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"

    When I fill in "E-mail address" with "iodine@ankh.am"
    And I fill in "Password" with "10d1ne"
    And I press "Log in"
    Then I should see the heading "Reannual plants"
    And I should see the success message "You have been logged in."
    And I should see the success message "You already are a member of Reannual plants."
    And I should not see the text "Welcome to Reannual plants"

    # Now repeat the whole procedure for a closed collection.
    Given I am an anonymous user
    When I go to the homepage of the "Vul nuts" collection
    Then I should see the link "Join this collection"
    But I should not see the link "Leave this collection"

    When I click "Join this collection"
    Then I should see the text "Sign in to join"
    And I should see "Only signed in users can join this collection. Please sign in or register an account on EU Login."
    But the cookie that tracks which group I want to join should not be set
    When I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"
    And a cookie should be set that allows me to join "Vul nuts" after authenticating

    When I fill in "E-mail address" with "iodine@ankh.am"
    And I fill in "Password" with "10d1ne"
    And I press "Log in"
    Then I should see the heading "Vul nuts"
    And I should see the success message "You have been logged in."
    And I should see the success message "Your membership to the Vul nuts collection is under approval."
    And the "Vul nuts" collection should have 1 pending member
    And a modal should open
    And I should see the text "Welcome to Vul nuts" in the "Modal title"
    And I should see the text "Want to receive notifications, too?" in the "Modal content"
    And I should see the button "No thanks" in the "Modal buttons" region
    And I should see the button "Subscribe" in the "Modal buttons" region

    Given I am an anonymous user
    When I go to the homepage of the "Vul nuts" collection
    And I click "Join this collection"
    And I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"

    When I fill in "E-mail address" with "iodine@ankh.am"
    And I fill in "Password" with "10d1ne"
    And I press "Log in"
    Then I should see the heading "Vul nuts"
    And I should see the success message "You have already joined the Vul nuts collection but your membership still needs to be approved by a facilitator."
    And I should not see the text "Welcome to Vul nuts"

    # Clean up the user that was created manually during the scenario.
    Then I delete the "Iodine Maccalariat" user

  @javascript
  Scenario: Logged out users can join a collection after logging in
    Given users:
      | Username       |
      | Daniel Trooper |
    And CAS users:
      | Username       | E-mail         | Password | Local username |
      | Daniel Trooper | daniel@ankh.am | dan1e7   | Daniel Trooper |

    # Anonymous users should be able to join a collection but not leave one.
    Given I am an anonymous user
    When I go to the homepage of the "Reannual plants" collection
    # These are links which are styled as buttons.
    Then I should see the link "Join this collection"
    But I should not see the link "Leave this collection"

    When I click "Join this collection"
    Then I should see the text "Sign in to join"
    And I should see "Only signed in users can join this collection. Please sign in or register an account on EU Login."
    But the cookie that tracks which group I want to join should not be set
    When I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"
    And a cookie should be set that allows me to join "Reannual plants" after authenticating

    When I fill in "E-mail address" with "daniel@ankh.am"
    And I fill in "Password" with "dan1e7"
    And I press "Log in"

    # The user should be redirected to the collection so they can opt in to
    # receiving notifications.
    Then I should see the heading "Reannual plants"
    And I should see the success message "You have been logged in."
    And I should see the success message "You are now a member of Reannual plants."
    And the "Reannual plants" collection should have 1 active member
    And a modal should open
    # Quick check without pressing buttons, this is fully tested in the
    # subscribe-on-join scenario.
    And I should see the text "Welcome to Reannual plants" in the "Modal title"
    And I should see the text "Want to receive notifications, too?" in the "Modal content"
    And I should see the button "No thanks" in the "Modal buttons" region
    And I should see the button "Subscribe" in the "Modal buttons" region

    # The modal should not open when visiting the page for a second time.
    When I reload the page
    And I wait for AJAX to finish
    Then I should not see the text "Welcome to Reannual plants"

    # Repeat the scenario for a closed collection.
    Given I am an anonymous user
    When I go to the homepage of the "Vul nuts" collection
    Then I should see the link "Join this collection"
    But I should not see the link "Leave this collection"

    When I click "Join this collection"
    Then I should see the text "Sign in to join"
    And I should see "Only signed in users can join this collection. Please sign in or register an account on EU Login."
    But the cookie that tracks which group I want to join should not be set
    When I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"
    And a cookie should be set that allows me to join "Vul nuts" after authenticating

    When I fill in "E-mail address" with "daniel@ankh.am"
    And I fill in "Password" with "dan1e7"
    And I press "Log in"

    Then I should see the heading "Vul nuts"
    And I should see the success message "You have been logged in."
    And I should see the success message "Your membership to the Vul nuts collection is under approval."
    And the "Vul nuts" collection should have 1 pending member
    And a modal should open
    And I should see the text "Welcome to Vul nuts" in the "Modal title"
    And I should see the text "Want to receive notifications, too?" in the "Modal content"
    And I should see the button "No thanks" in the "Modal buttons" region
    And I should see the button "Subscribe" in the "Modal buttons" region

    When I reload the page
    And I wait for AJAX to finish
    Then I should not see the text "Welcome to Vul nuts"

  Scenario: Show relevant message to a blocked user that tries to rejoin a collection as anonymous
    Given users:
      | Username         |
      | Hepzibah Whitlow |
    And CAS users:
      | Username         | E-mail           | Password | Local username   |
      | Hepzibah Whitlow | hepzibah@ankh.am | wh1tl0w  | Hepzibah Whitlow |
    And collection user membership:
      | collection      | user             | state   |
      | Reannual plants | Hepzibah Whitlow | blocked |

    Given I am an anonymous user
    When I go to the homepage of the "Reannual plants" collection
    And I click "Join this collection"
    Then I should see the text "Sign in to join"
    When I press "Sign in / Register"
    Then I should see the heading "Sign in to continue"

    When I fill in "E-mail address" with "hepzibah@ankh.am"
    And I fill in "Password" with "wh1tl0w"
    And I press "Log in"

    Then I should see the heading "Reannual plants"
    And I should see the success message "You have been logged in."
    And I should see the success message "You cannot join Reannual plants because your account has been blocked."

    # The message should only be shown once.
    When I reload the page
    And I should not see the success message "You cannot join Reannual plants because your account has been blocked."
