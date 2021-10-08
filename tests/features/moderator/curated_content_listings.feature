@api @group-b
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
    Then I should see the heading "Update curated content listing Discover topics"
    And I should see the button "Add item"
    And I should see the button "Save"
    And I should see the button "Reverse"
    And I should see the button "Shuffle"
    And I should see the button "Clear"

  Scenario: Moderators can quickly access curated content listing pages.
    Given I am an anonymous user
    And I am on the homepage
    Then I should not see any contextual links in the "In the spotlight" region
    And I should not see any contextual links in the "Highlighted event" region
    And I should not see any contextual links in the "Highlighted solution" region

    Given I am logged in as an "authenticated user"
    And I am on the homepage
    Then I should not see any contextual links in the "In the spotlight" region
    And I should not see any contextual links in the "Highlighted event" region
    And I should not see any contextual links in the "Highlighted solution" region

    Given I am logged in as a "moderator"
    And I am on the homepage
    Then I should see the contextual link "Update curated content" in the "In the spotlight" region
    And I should see the contextual link "Update curated content" in the "Highlighted event" region
    And I should see the contextual link "Update curated content" in the "Highlighted solution" region

    Given I am on the homepage
    When I click the contextual link "Update curated content" in the "In the spotlight" region
    Then I should see the heading "Update curated content listing In the spotlight"

    Given I am on the homepage
    When I click the contextual link "Update curated content" in the "Highlighted event" region
    Then I should see the heading "Update curated content listing Highlighted event"

    Given I am on the homepage
    When I click the contextual link "Update curated content" in the "Highlighted solution" region
    Then I should see the heading "Update curated content listing Highlighted solution"
