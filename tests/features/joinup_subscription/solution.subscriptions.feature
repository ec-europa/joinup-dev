@api @group-b
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

  Scenario: Subscribe to a solution as an anonymous user
    When I am not logged in
    And I go to the "Some solution to subscribe" solution
    # This is a link which is styled as a button.
    Then I should see the link "Subscribe to this solution"

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
