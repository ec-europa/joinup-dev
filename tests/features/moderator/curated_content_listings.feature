@api @group-a
Feature: Curated content listings
  In order to manage the content which is highlighted on the frontpage
  As a moderator
  I should be able to curate content listings

  Scenario: Access the dashboard
    Given I am logged in as a "moderator"
    When I click "Dashboard"
    And I click "Curated content listings"
    Then I should see the heading "Curated content listings"
    And I should see the following links:
      | Discover topics      |
      | Highlighted event    |
      | Highlighted solution |
      | In the spotlight     |
    When I click "Discover topics"
    Then I should see the heading "Edit subqueue Discover topics"
    And I should see the button "Add item"
    And I should see the button "Save"
    And I should see the button "Reverse"
    And I should see the button "Shuffle"
    And I should see the button "Clear"
