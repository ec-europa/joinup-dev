@api
Feature: User subscription settings
  As a user I must be able to set and view my subscription settings.

  Scenario: Set the subscription settings
    Given I am logged in as a user with the "authenticated user" role
    When I am on the homepage
    And I click "Dashboard"
    Then I should see the link "My subscriptions"
    When I click "My subscriptions"
    And I select "monthly" from "field_user_frequency"
    # Check "Receive notifications for": Solution.
    Then I check "field_user_group_types[rdf_entity:solution]"
    # Check "Receive notifications for": News.
    Then I check "field_user_group_types[node:news]"
    # Check "Notify me on updates".
    And I check "field_user_subscription_events[value]"
    And I press "Save"
    Then I should see the following success messages:
     | The changes have been saved |

    # Check that the settings have been saved and are visible.
    When I am on the homepage
    And I click "My account"
    Then I should see the text "Notification frequency"
    And I should see the text "Monthly"
    And I should see the text "Receive notifications for"
    And I should see the text "Solution"
    And I should see the text "News"
    And I should see the text "Notify me on updates"