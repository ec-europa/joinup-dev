@api @group-d
Feature: Subscribing to a solution
  In order to promote my solution
  As a solution owner
  I want to persuade new members to subscribe to my solution

  Background:
    Given collection:
      | title       | Some parent collection |
      | abstract    | Abstract               |
      | description | Description            |
      | closed      | yes                    |
      | state       | validated              |
    And solution:
      | title      | Some solution to subscribe |
      | state      | validated                  |
      | collection | Some parent collection     |
    And users:
      | Username          |
      | Cornilius Darcias |

  @javascript
  Scenario: Subscribe to a solution as an anonymous user
    Given CAS users:
      | Username         | E-mail      | Password |
      | Jonathan Teatime | jon@ankh.am | j0nathan |
    And the following legal document version:
      | Document     | Label | Published | Acceptance label                                                                                   | Content                                                    |
      | Legal notice | 1.1   | yes       | I have read and accept the <a href="[entity_legal_document:url]">[entity_legal_document:label]</a> | The information on this site is subject to a disclaimer... |

    When I am not logged in
    And I go to the "Some solution to subscribe" solution
    # This is a link which is styled as a button.
    Then I should see the link "Subscribe to this solution"
    And the "Some solution to subscribe" solution should have 0 active members

    When I click "Subscribe to this solution"
    Then I should see the text "Sign in to subscribe"
    And I should see "Only signed in users can subscribe to this solution. Please sign in or register an account on EU Login."
    But the cookie that tracks which group I want to join should not be set
    When I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"
    And a cookie should be set that allows me to join "Some solution to subscribe" after authenticating

    When I fill in "E-mail address" with "jon@ankh.am"
    And I fill in "Password" with "j0nathan"
    And I press "Log in"
    And I select the radio button "I am a new user (create a new account)"
    And I check the "I have read and accept the Legal notice" material checkbox
    And I press "Next"

    # The user should be redirected to the solution and be presented with a
    # welcome message.
    Then I should see the heading "Some solution to subscribe"
    And I should see the success message "You have been logged in."
    And I should see the success message "You have subscribed to this solution and will receive notifications for it. To manage your subscriptions go to My subscriptions in your user menu."
    And the "Some solution to subscribe" solution should have 1 active member

    # Now that the user is subscribed, if we log out and try to subscribe again
    # as an anonymous user, we should be shown an appropriate message.
    When I am not logged in
    And I go to the "Some solution to subscribe" solution
    And I click "Subscribe to this solution"
    And I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"

    When I fill in "E-mail address" with "jon@ankh.am"
    And I fill in "Password" with "j0nathan"
    And I press "Log in"
    Then I should see the heading "Some solution to subscribe"
    And I should see the success message "You have been logged in."
    And I should see the success message "You are already subscribed to Some solution to subscribe."

    # Clean up the user that was created manually during the scenario.
    Then I delete the "Jonathan Teatime" user

  @javascript
  Scenario: Logged out users can subscribe to a solution after logging in
    Given users:
      | Username                  |
      | Bergholt Stuttley Johnson |
    Given CAS users:
      | Username                  | E-mail                   | Password | Local username            |
      | Bergholt Stuttley Johnson | berg@stuttley-johnson.am | berghol7 | Bergholt Stuttley Johnson |

    When I am not logged in
    And I go to the "Some solution to subscribe" solution
    # This is a link which is styled as a button.
    Then I should see the link "Subscribe to this solution"
    And the "Some solution to subscribe" solution should have 0 active members

    When I click "Subscribe to this solution"
    Then I should see the text "Sign in to subscribe"
    And I should see "Only signed in users can subscribe to this solution. Please sign in or register an account on EU Login."
    But the cookie that tracks which group I want to join should not be set
    When I press "Sign in / Register" in the "Modal buttons" region
    Then I should see the heading "Sign in to continue"
    And a cookie should be set that allows me to join "Some solution to subscribe" after authenticating

    When I fill in "E-mail address" with "berg@stuttley-johnson.am"
    And I fill in "Password" with "berghol7"
    And I press "Log in"

    # The user should be redirected to the solution and be presented with a
    # welcome message.
    Then I should see the heading "Some solution to subscribe"
    And I should see the success message "You have been logged in."
    And I should see the success message "You have subscribed to this solution and will receive notifications for it. To manage your subscriptions go to My subscriptions in your user menu."
    And the "Some solution to subscribe" solution should have 1 active member

  Scenario: Show relevant message to a blocked user that tries to resubscribe as anonymous
    Given users:
      | Username     |
      | Cosmo Lavish |
    And CAS users:
      | Username     | E-mail             | Password | Local username |
      | Cosmo Lavish | cosmo@lavish.co.am | c0sm0    | Cosmo Lavish   |
    And solution user membership:
      | solution                   | user         | state   |
      | Some solution to subscribe | Cosmo Lavish | blocked |

    Given I am an anonymous user
    When I go to the homepage of the "Some solution to subscribe" solution
    And I click "Subscribe to this solution"
    And I press "Sign in / Register"
    Then I should see the heading "Sign in to continue"

    When I fill in "E-mail address" with "cosmo@lavish.co.am"
    And I fill in "Password" with "c0sm0"
    And I press "Log in"

    Then I should see the heading "Some solution to subscribe"
    And I should see the success message "You have been logged in."
    And I should see the success message "You cannot subscribe to Some solution to subscribe because your account has been blocked."

    # The message should only be shown once.
    When I reload the page
    And I should not see the success message "You cannot subscribe to Some solution to subscribe because your account has been blocked."

  @javascript
  Scenario: Subscribe to a solution as a normal user
    When I am logged in as "Cornilius Darcias"
    And I go to the "Some solution to subscribe" solution
    Then I should see the button "Subscribe to this solution"

    When I press "Subscribe to this solution"
    Then I should see the success message "You have subscribed to this solution and will receive notifications for it. To manage your subscriptions go to My subscriptions in your user menu."

    When I open the account menu
    And I click "My subscriptions"
    Then I should see the heading "My subscriptions"
    And I should see the text "Some solution to subscribe"
    And the "Save changes" button on the "Some solution to subscribe" subscription card should be disabled

    # For solutions, all bundles are selected by default.
    And the following content subscriptions should be selected:
      | Some solution to subscribe | Discussion, Document, Event, News |
    # The button "Unsubscribe from all" is visible.
    And I should see the link "Unsubscribe from all"

    Given I uncheck the "Discussion" checkbox of the "Some solution to subscribe" subscription
    Then the "Save changes" button on the "Some solution to subscribe" subscription card should be enabled
    When I press "Save changes" on the "Some solution to subscribe" subscription card
    And I wait for AJAX to finish
    Then I should not see the "Save changes" button on the "Some solution to subscribe" subscription card
    But I should see the "Saved!" button on the "Some solution to subscribe" subscription card
    And the following content subscriptions should be selected:
      | Some solution to subscribe | Document, Event, News |

    When I go to the "Some solution to subscribe" solution
    And I press "You're a member"
    And I wait for animations to finish
    And I click "Unsubscribe from this solution"
    And a modal should open

    Then I should see the following lines of text:
      | Leave solution                                                                                                |
      | Are you sure you want to leave the Some solution to subscribe solution?                                       |
      | By leaving the solution you will be no longer able to publish content in it or receive notifications from it. |

  @javascript
  Scenario Outline: Authors and facilitators see "Leave this solution" instead of "Unsubscribe from this solution".
    Given the following solution user membership:
      | solution                   | user              | roles  |
      | Some solution to subscribe | Cornilius Darcias | <role> |
    When I am logged in as "Cornilius Darcias"
    And I go to the "Some solution to subscribe" solution
    And I press "You're a member"
    And I wait for animations to finish
    And I click "<label>"

    Examples:
      | role        | label                          |
      |             | Unsubscribe from this solution |
      | author      | Leave this solution            |
      | facilitator | Leave this solution            |
