@api
Feature: Subscribing to a collection after joining
  In order to promote my collection
  As a collection owner
  I want to persuade new members to subscribe to my collection

  @javascript
  Scenario: Show a modal dialog asking a user to subscribe after joining
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

    When I am logged in as "Cornilius Darcias"
    And I go to the "Some solution to subscribe" solution
    Then I should see the button "Subscribe to this solution"

    When I press "Subscribe to this solution"
    Then I should see the success message "You have subscribed to this solution and will receive notifications for it. You can manage your subscriptions at My subscriptions"

    When I open the account menu
    And I click "My subscriptions"
    Then I should see the heading "My subscriptions"
    And I should see the link "Collections" in the "Content" region
    And I should see the link "Solutions" in the "Content" region

    When I click "Solutions" in the "Content" region
    Then I should see the text "Some solution to subscribe"
    And the "Save changes" button on the "Some solution to subscribe" subscription card should be disabled

    # For solutions, all bundles are selected by default.
    And the following solution content subscriptions should be selected:
      | Some solution to subscribe | Discussion, Document, Event, News |
    # The button "Unsubscribe from all" is visible.
    And I should see the link "Unsubscribe from all"

    Given I uncheck the "Discussion" checkbox of the "Some solution to subscribe" subscription
    Then the "Save changes" button on the "Some solution to subscribe" subscription card should be enabled
    When I press "Save changes" on the "Some solution to subscribe" subscription card
    And I wait for AJAX to finish
    Then I should not see the "Save changes" button on the "Some solution to subscribe" subscription card
    But I should see the "Saved!" button on the "Some solution to subscribe" subscription card
    And the following solution content subscriptions should be selected:
      | Some solution to subscribe | Document, Event, News |
