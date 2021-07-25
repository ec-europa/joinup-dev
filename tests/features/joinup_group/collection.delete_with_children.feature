@api
Feature:
  In order to not leave orphaned entities
  As a community owner
  I need to be unable to delete communities that have children entities.

  Scenario Outline: Delete a community with a child solution
    Given community:
      | title | Community with child |
      | state | <community state>    |
    And solution:
      | title      | Child of community with child |
      | state      | <solution state>               |
      | community | Community with child          |

    When I am logged in as a moderator
    And I go to the delete form of the "Community with child" community
    Then I should not see the button "Delete"
    And I should see the heading "The community Community with child cannot be deleted because it contains the following solutions:"
    But I should see the link "Child of community with child"
    And I should see the text "You can delete your solutions or transfer them to another community."

    Given I delete the "Child of community with child" solution
    And I go to the delete form of the "Community with child" community
    Then I should see the heading "Are you sure you want to delete community Community with child?"
    And I should see the text "This action cannot be undone."
    When I press "Delete"
    Then I should be on the homepage
    And I should have 0 communities

    Examples:
      | community state | solution state |
      | validated        | validated      |
      | validated        | draft          |
      | draft            | validated      |
      | draft            | draft          |
