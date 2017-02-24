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
    Then I should see the success message "The changes have been saved"

    # Check that the settings have been saved.
    When I am on the homepage
    And I click "My account"
    And I click "Subscription Settings"
    #Then the option "monthly" should be selected
    Then the radio "Monthly" from field "Notification frequency" should be checked
    And the "Solution" checkbox should be checked
    And the "News" checkbox should be checked
    And the "Notify me on updates" checkbox should be checked
    But the "Release" checkbox should not be checked
    And the "Distribution" checkbox should not be checked
    And the "Document" checkbox should not be checked
    And the "Event" checkbox should not be checked
    And the "Custom page" checkbox should not be checked
