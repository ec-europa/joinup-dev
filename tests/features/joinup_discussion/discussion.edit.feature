@api @group-c
Feature: "Discussion page" editing.
  In order to manage discussions
  As an owner of the discussion
  I need to be able to edit it.

  # This is a smokescreen test as the full behaviour should be tested in workflows.
  # @todo: To be removed after ISAICP-2265 is implemented.
  # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-2265
  Scenario Outline: Owners and moderators should be able to view the Edit link.
    Given users:
      | Username |
      | <user>   |
    And the following collections:
      | title        | description        | state     |
      | <collection> | Sample description | validated |
    And the following collection user memberships:
      | collection   | user   | roles  |
      | <collection> | <user> | <role> |
    And "discussion" content:
      | title        | author | collection   | state     |
      | <discussion> | <user> | <collection> | validated |
    When I am logged in as "<user>"
    And I go to the "<discussion>" discussion
    Then I should see the link "Edit" in the "Entity actions" region
    # A moderator should always be able to edit the content.
    When I am logged in as a moderator
    And I go to the "<discussion>" discussion
    Then I should see the link "Edit" in the "Entity actions" region
    Examples:
      | user           | collection      | discussion             | role        |
      | Bennie Sherman | Missing Lover   | The Seventh Planet     | member      |
      | Kristy Cortez  | Witch of Secret | The Waves of the Flame | facilitator |

  Scenario: A solution facilitator can edit his content.
    Given users:
      | Username      |
      | Toni Holloway |
    And the following solutions:
      | title                | description        | state     |
      | Lights in the Shards | Sample description | validated |
    And the following solution user memberships:
      | solution             | user          | roles       |
      | Lights in the Shards | Toni Holloway | facilitator |
    And "discussion" content:
      | title         | author        | solution             | state     |
      | Fallen Flames | Toni Holloway | Lights in the Shards | validated |
    When I am logged in as "Toni Holloway"
    And I go to the "Fallen Flames" discussion
    Then I should see the link "Edit" in the "Entity actions" region
    # A moderator should always be able to edit the content.
    When I am logged in as a moderator
    And I go to the "Fallen Flames" discussion
    Then I should see the link "Edit" in the "Entity actions" region
