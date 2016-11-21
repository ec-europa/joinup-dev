@api
Feature: "Event page" editing.
  In order to manage events
  As an owner of the event
  I need to be able to edit it.

  # This is a smokescreen test as the full behaviour should be tested in workflows.
  # @todo: To be removed after ISAICP-2268 is implemented.
  # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2268
  Scenario Outline:
    Given users:
      | name   |
      | <user> |
    And the following collections:
      | title        | description        | state     |
      | <collection> | Sample description | validated |
    And the following collection user memberships:
      | collection   | user   | roles  |
      | <collection> | <user> | <role> |
    And "event" content:
      | title   | author | collection   |
      | <event> | <user> | <collection> |
    When I am logged in as "<user>"
    And I go to the "<event>" event
    Then I should see the link "Edit" in the "Entity actions" region
    # A moderator should always be able to edit the content.
    When I am logged in as a moderator
    And I go to the "<event>" discussion
    Then I should see the link "Edit" in the "Entity actions" region
    Examples:
      | user          | collection        | event                    | role        |
      | Irvin West    | Bare Past         | Name of Consort          | member      |
      | Emilio Garcia | The Final Bridges | The Dreaming of the Game | facilitator |

  Scenario: A solution facilitator can edit his content.
    Given users:
      | name        |
      | Krista Garrett |
    And the following solutions:
      | title                | description        | state     |
      | Dreamer in the Snake | Sample description | validated |
    And the following solution user memberships:
      | solution             | user        | roles       |
      | Dreamer in the Snake | Krista Garrett | facilitator |
    And "event" content:
      | title       | author      | solution             |
      | Silver Snow | Krista Garrett | Dreamer in the Snake |
    When I am logged in as "Krista Garrett"
    And I go to the "Silver Snow" event
    Then I should see the link "Edit" in the "Entity actions" region
    # A moderator should always be able to edit the content.
    When I am logged in as a moderator
    And I go to the "Silver Snow" discussion
    Then I should see the link "Edit" in the "Entity actions" region
