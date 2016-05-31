@api
Feature: "Add news" visibility options.
  In order to manage news
  As a collection member
  I need to be able to add news content through UI.

  Scenario: "Add news" button should only be shown to moderators.
    Given users:
      | name         | pass | mail                     | roles     |
      | Pepper Pots  | pass | pepper.pots@example.com  | moderator |
      | Tony Stark   | pass | tony.stark@example.com   |           |
      | Phil Coulson | pass | phil.coulson@example.com |           |
    And the following collection:
      | title      | Ironman's home |
      | logo       | logo.png       |
      | moderation | yes            |
    And user memberships:
      | collection     | user         | roles         |
      | Ironman's home | Pepper Pots  |               |
      | Ironman's home | Tony Stark   | administrator |
      | Ironman's home | Phil Coulson | member        |

    # Check visibility for authenticated users.
    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Ironman's home" collection
    Then I should not see the link "Add news"
    # Check visibility for users with specific permissions.
    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Ironman's home" collection
    Then I should not see the link "Add news"
    # Group members can create news.
    When I am logged in as "Phil Coulson"
    And I go to the homepage of the "Ironman's home" collection
    Then I should see the link "Add news"

    # Add news belonging to a collection.
    When I click "Add news"
    Then I should see the heading "Add news"
    And the following fields should be present "Headline, Kicker, Content, State"
    And the following fields should not be present "Groups audience"
    When I fill in the following:
      | Headline | New avengers member                  |
      | Kicker   | Black Widow                          |
      | Content  | Specialized in close combat training |
    And I select "Draft" from "State"
    And I press "Save"
    # Check reference to news page.
    Then I should see the heading "New avengers member"
    When I go to the homepage of the "Ironman's home" collection
    Then I should see the link "New avengers member"
