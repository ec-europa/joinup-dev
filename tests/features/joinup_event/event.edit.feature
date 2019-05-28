@api
Feature: "Event page" editing.
  In order to manage events
  As an owner of the event
  I need to be able to edit it.

  Scenario: Add and remove map
    Given collections:
      | title            | logo     | banner     | state     |
      | Stream of Dreams | logo.png | banner.jpg | validated |
    And I am logged in as a facilitator of the "Stream of Dreams" collection
    When I go to the homepage of the "Stream of Dreams" collection
    And I click "Add event" in the plus button menu
    When I fill in the following:
      | Title             | An amazing event                      |
      | Description       | This is going to be an amazing event. |
      | Physical location | Rue Belliard 28, Brussels, Belgium    |
    And I press "Save as draft"
    And I should see a map on the page
    When I click the contextual link "Edit" in the Header region
    When I clear the field "Physical location"
    And I press "Save as draft"
    And I should not see a map on the page

  Scenario Outline: Owners and moderators should be able to view the Edit link.
    Given users:
      | Username |
      | <user>   |
    And the following collections:
      | title        | description        | state     | moderation |
      | <collection> | Sample description | validated | yes        |
    And the following collection user memberships:
      | collection   | user   | roles  |
      | <collection> | <user> | <role> |
    And "event" content:
      | title   | author | collection   | state     |
      | <event> | <user> | <collection> | validated |
    When I am logged in as "<user>"
    And I go to the "<event>" event
    Then I should see the link "Edit" in the "Entity actions" region
    # A moderator should always be able to edit the content.
    When I am logged in as a moderator
    And I go to the "<event>" event
    Then I should see the link "Edit" in the "Entity actions" region
    Examples:
      | user           | collection        | event                    | role        |
      | Kristina Silva | Gate of Flames    | Gate of Flames           |             |
      | Irvin West     | Bare Past         | Name of Consort          | member      |
      | Emilio Garcia  | The Final Bridges | The Dreaming of the Game | facilitator |

  Scenario: A solution facilitator can edit his content.
    Given users:
      | Username       |
      | Krista Garrett |
    And the following solutions:
      | title                | description        | state     | moderation |
      | Dreamer in the Snake | Sample description | validated | no         |
    And the following solution user memberships:
      | solution             | user           | roles       |
      | Dreamer in the Snake | Krista Garrett | facilitator |
    And "event" content:
      | title       | author         | solution             | state    |
      | Silver Snow | Krista Garrett | Dreamer in the Snake | proposed |
    When I am logged in as "Krista Garrett"
    And I go to the "Silver Snow" event
    Then I should see the link "Edit" in the "Entity actions" region
    # A moderator should always be able to edit the content.
    When I am logged in as a moderator
    And I go to the "Silver Snow" event
    Then I should see the link "Edit" in the "Entity actions" region
