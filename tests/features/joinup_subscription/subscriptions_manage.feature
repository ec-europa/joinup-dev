@api
Feature: User subscription settings
  As a user I must be able to set and view my subscription settings.

  Scenario: Set the subscription settings
    Given I am logged in as a user with the "authenticated user" role
    When I am on the homepage
    And I click "My account"
    Then I should see the link "Subscription Settings"
    When I click "Subscription Settings"
    And I select "Monthly" from "Frequency"
    And I select "Solution" from "Subscription group types"
    And I press "Save"
    Then I should see the following success messages:
     | The changes have been saved |

    # Check that the settings have been saved and are visible.
    When I am on the homepage
    And I click "My account"
    Then I should see the text "Frequency"
    And I should see the text "Monthly"
    And I should see the text "Subscription group types"
    And I should see the text "Solution"