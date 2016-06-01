@api
Feature: "Add news" visibility options.
  In order to manage news
  As a collection member
  I need to be able to add news content through UI.

  Scenario: "Add news" button should only be shown to moderators.
    Given users:
      | name         | mail                     | roles     |
      | Pepper Pots  | pepper.pots@example.com  | moderator |
      | Tony Stark   | tony.stark@example.com   |           |
      | Phil Coulson | phil.coulson@example.com |           |
    And collections:
      | title                 | logo     | moderation |
      | Ironman's home        | logo.png | yes        |
      | S.H.I.E.L.D. newsroom | logo.png | no         |
    And user memberships:
      | collection            | user         | roles         |
      | Ironman's home        | Tony Stark   | administrator |
      | Ironman's home        | Phil Coulson | member        |
      | S.H.I.E.L.D. newsroom | Phil Coulson | member        |
    # Check visibility for anonymous users.
    When I am not logged in
    And I go to the homepage of the "Ironman's home" collection
    Then I should not see the link "Add news"
    # Check visibility for authenticated users.
    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Ironman's home" collection
    Then I should not see the link "Add news"
    # Only group 'members' and site moderators can create news.
    When I am logged in as "Tony Stark"
    And I go to the homepage of the "Ironman's home" collection
    Then I should not see the link "Add news"
    When I am logged in as "Pepper Pots"
    And I go to the homepage of the "Ironman's home" collection
    Then I should see the link "Add news"
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
    And I should see the text "Collection"
    And I should see the text "Ironman's home"
    When I click "Ironman's home"
    Then I should see the link "New avengers member"

    # Add news belonging to a collection with status validated.
    When I go to the homepage of the "S.H.I.E.L.D. newsroom" collection
    And I click "Add news"
    And I fill in the following:
      | Headline | Avengers acquired Black Widow |
      | Kicker   | Recruitment alert             |
      | Content  | We just missed her            |
    And I select "Validated" from "State"
    And I press "Save"
    Then I should see the heading "Avengers acquired Black Widow"
    And I should see the text "Collection"
    And I should see the text "S.H.I.E.L.D. newsroom"