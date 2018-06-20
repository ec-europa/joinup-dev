@api
Feature: "Event page" editing.
  In order to manage events
  As an owner of the event
  I need to be able to edit it.

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
