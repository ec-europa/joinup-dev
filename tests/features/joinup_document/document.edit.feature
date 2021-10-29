@api @group-d
Feature: "Document page" editing.
  In order to manage documents
  As an owner of the document
  I need to be able to edit it.

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
    And "document" content:
      | title      | author | collection   | state     |
      | <document> | <user> | <collection> | validated |
    When I am logged in as "<user>"
    And I go to the "<document>" document
    Then I should see the link "Edit" in the "Entity actions" region
    # A moderator should always be able to edit the content.
    When I am logged in as a moderator
    And I go to the "<document>" document
    Then I should see the link "Edit" in the "Entity actions" region
    Examples:
      | user           | collection      | document               | role        |
      | Billie Stanley | Seventh Shores  | Fire of Female         | member      |
      | Tamara Kelley  | The Bold Stones | The Tales of the Twins | facilitator |

  Scenario: A solution facilitator can edit his content.
    Given users:
      | Username    |
      | Peter Floyd |
    And the following solutions:
      | title                  | description        | state     |
      | Predator in the Future | Sample description | validated |
    And the following solution user memberships:
      | solution               | user        | roles       |
      | Predator in the Future | Peter Floyd | facilitator |
    And "document" content:
      | title        | author      | solution               | state     |
      | Prized Cloud | Peter Floyd | Predator in the Future | validated |
    When I am logged in as "Peter Floyd"
    And I go to the "Prized Cloud" document
    Then I should see the link "Edit" in the "Entity actions" region
    # A moderator should always be able to edit the content.
    When I am logged in as a moderator
    And I go to the "Prized Cloud" document
    Then I should see the link "Edit" in the "Entity actions" region
