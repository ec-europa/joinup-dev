@api @terms @newsroom
Feature: Subscribing to collection newsletters
  In order to promote my collection
  As a collection owner
  I want to be allow members to subscribe to my newsletter

  Background:
    Given users:
      | Username           |
      | Filippos Demetriou |
      | Tatiana Andreas    |
    And the following contact:
      | email | charalambos.demetriou@example.com |
      | name  | Charalambos Demetriou             |
    And the following owner:
      | name              |
      | Antonios Katsaros |
    And the following collections:
      | title    | description             | logo     | banner     | owner             | contact information   | state     | policy domain           |
      | Volkor X | We do not come in peace | logo.png | banner.jpg | Antonios Katsaros | Charalambos Demetriou | validated | Statistics and Analysis |
    And the following collection user memberships:
      | collection | user               | roles       |
      | Volkor X   | Filippos Demetriou | owner       |
      | Volkor X   | Tatiana Andreas    | facilitator |

  # This is a temporary measure. The newsletter subscription possibility is
  # currently only available for moderators while it is being evaluated. If this
  # works well it can be unlocked for all other collections in the future and it
  # will be possible for owners and/or facilitators to edit this data.
  Scenario: Only moderators can enter the subscription information for a collection.
    Given I am logged in as a moderator
    And I go to the "Volkor X" collection
    And I click the contextual link "Edit" in the Header region
    Then the following fields should be present "Enable newsletter subscriptions, Universe acronym, Newsletter service ID"

    When I am logged in as "Filippos Demetriou"
    And I go to the "Volkor X" collection
    And I click the contextual link "Edit" in the Header region
    Then the following fields should not be present "Enable newsletter subscriptions, Universe acronym, Newsletter service ID"

    When I am logged in as "Tatiana Andreas"
    And I go to the "Volkor X" collection
    And I click the contextual link "Edit" in the Header region
    Then the following fields should not be present "Enable newsletter subscriptions, Universe acronym, Newsletter service ID"
