@api
Feature:
  In order to not leave orphaned entities
  As a collection owner
  I need to be unable to delete collections that have children entities.

  @terms
  Scenario Outline: Delete a collection with a child solution
    Given collection:
      | title | Collection with child |
      | state | <collection state>    |
    And solution:
      | title      | Child of collection with child |
      | state      | <solution state>               |
      | collection | Collection with child          |

    When I am logged in as a moderator
    And I go to the "Collection with child" collection delete form
    Then I should not see the button "Delete"
    And I should see the heading "The collection Collection with child cannot be deleted because it contains the following solutions:"
    But I should see the link "Child of collection with child"
    And I should see the text "You can delete your solutions or transfer them to another collection."

    Examples:
      | collection state | solution state |
      | validated        | validated      |
      | validated        | draft          |
      | draft            | validated      |
      | draft            | draft          |
