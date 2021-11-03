@api @terms @group-c
Feature: Subscribing to collection newsletters
  In order to promote my collection
  As a collection owner
  I want to be allow members to subscribe to my newsletter

  Background:
    Given users:
      | Username           | E-mail                         | Roles     |
      | Filippos Demetriou | filippos.demetriou@example.com |           |
      | Tatiana Andreas    | tatiandri@example.com          |           |
      | Magdalini Kokinos  | m.kokinos@example.com          | moderator |
    And the following contact:
      | email | charalambos.demetriou@example.com |
      | name  | Charalambos Demetriou             |
    And the following owner:
      | name              |
      | Antonios Katsaros |
    And the following collections:
      | title    | description             | logo     | banner     | owner             | contact information   | state     | topic                   |
      | Volkor X | We do not come in peace | logo.png | banner.jpg | Antonios Katsaros | Charalambos Demetriou | validated | Statistics and Analysis |
    And "news" content:
      | title      | collection | state     | author          |
      | Hypersleep | Volkor X   | validated | Tatiana Andreas |
    And the following collection user memberships:
      | collection | user               | roles       |
      | Volkor X   | Filippos Demetriou | owner       |
      | Volkor X   | Tatiana Andreas    | facilitator |

  Scenario: Only moderators can enter the subscription information for a collection.
    Given I am logged in as a moderator
    And I go to the "Volkor X" collection
    When I click "Edit" in the "Entity actions" region
    Then the following fields should be present "Enable newsletter subscriptions, Universe acronym, Newsletter service ID"

    When I am logged in as "Filippos Demetriou"
    And I go to the "Volkor X" collection
    When I click "Edit" in the "Entity actions" region
    Then the following fields should not be present "Enable newsletter subscriptions, Universe acronym, Newsletter service ID"

    When I am logged in as "Tatiana Andreas"
    And I go to the "Volkor X" collection
    When I click "Edit" in the "Entity actions" region
    Then the following fields should not be present "Enable newsletter subscriptions, Universe acronym, Newsletter service ID"

  Scenario: Configure the newsletter subscription
    # When the newsletter subscriptions are not enabled the subscription form
    # should not be shown.
    Given I am logged in as a moderator
    And I go to the "Volkor X" collection
    Then I should not see the newsletter subscription form in the last tile

    # If "Enable newsletter subscriptions" is not checked then it should be
    # possible to submit the form without entering data in the two newsletter
    # fields.
    When I click "Edit" in the "Entity actions" region
    And I uncheck "Enable newsletter subscriptions"
    And I press "Publish"
    Then I should see the heading "Volkor X"

    # If "Enable newsletter subscriptions" is checked then the fields become
    # required and an error message should be shown if they are not filled in.
    When I click "Edit" in the "Entity actions" region
    And I check "Enable newsletter subscriptions"
    And I press "Publish"
    Then I should see the following error messages:
      | error messages                                                                     |
      | Universe acronym field is required when newsletter subscriptions are enabled.      |
      | Newsletter service ID field is required when newsletter subscriptions are enabled. |

    # Fill in the information to connect with the Newsroom newsletter service.
    # This should make it possible to submit the form without errors.
    When I fill in "Universe acronym" with "volkor-x"
    And I fill in "Newsletter service ID" with "123"
    And I press "Publish"
    Then I should see the heading "Volkor X"

    # Now the subscription form should show up.
    And I should see the newsletter subscription form in the last tile

    # Disable the newsletter subscriptions again. This should make the form
    # disappear.
    When I click "Edit" in the "Entity actions" region
    And I uncheck "Enable newsletter subscriptions"
    And I press "Publish"
    Then I should see the heading "Volkor X"
    But I should not see the newsletter subscription form in the last tile

  @javascript @newsroom_newsletter
  Scenario: Subscribe to a newsletter
    Given I am logged in as "Magdalini Kokinos"
    When I go to the "Volkor X" collection
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    And I check "Enable newsletter subscriptions"
    And I fill in "Universe acronym" with "volkor-x"
    And I fill in "Newsletter service ID" with "123"
    And I press "Publish"
    Then I should see the heading "Volkor X"

    # Now the subscription form should show up.
    And I should see the newsletter subscription form in the last tile

    # The email address of the logged in user should be prefilled.
    And the "E-mail address" field should contain "m.kokinos@example.com"

    # When the user subscribes a success message should be shown.
    When I press "Subscribe"
    And I wait for AJAX to finish
    Then I should see the following success messages:
      | success messages                             |
      | Thank you for subscribing to our newsletter. |

    # When a user resubscribes it is polite to inform about this.
    When I go to the "Volkor X" collection
    And I press "Subscribe"
    And I wait for AJAX to finish
    Then I should see the following success messages:
      | success messages                              |
      | You are already subscribed to our newsletter. |
