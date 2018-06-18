@api
Feature: As a site owner
  In order to avoid orphaned groups
  I should be able to prevent moderators from deleting all owners of a group.

  Background:
    Given users:
      | Username       | Roles     |
      | Group owner 1  |           |
      | Group owner 2  |           |
      | Group member   |           |
      | Site moderator | moderator |
    And the following collection:
      | title | An owned collection |
      | state | validated           |
    And the following solution:
      | title | An owned solution |
      | state | validated         |

  Scenario Outline: A privileged user cannot remove all owners of a group.
    Given the following <type> user memberships:
      | <type>          | user          | roles |
      | An owned <type> | Group owner 1 | owner |
      | An owned <type> | Group owner 2 | owner |
      | An owned <type> | Group member  |       |
    And I am logged in as "Site moderator"
    And I go to the homepage of the "<title>" <type>
    And I click "Members"
    And I select the "Group owner 1" row
    And I select the "Group owner 2" row

    # Try to delete all owners at once.
    And I select "Delete the selected membership(s)" from "Action"
    When I press "Apply to selected items"
    Then I should see the error message "You cannot delete all owners of the <type>."

    # Deleting owners when at least one remains is still possible.
    Given I deselect the "Group owner 1" row
    When I press "Apply to selected items"
    Then I should see "Delete the selected membership(s) was applied to 1 item."

    # Action is properly interrupted even if other memberships are about to be deleted.
    Given I select the "Group owner 1" row
    And I select the "Group member" row
    And I select "Delete the selected membership(s)" from "Action"
    When I press "Apply to selected items"
    Then I should see the error message "You cannot delete the sole owner of the <type>."

    # Normal memberships can be deleted without an issue.
    Given I deselect the "Group owner 1" row
    And I select "Delete the selected membership(s)" from "Action"
    When I press "Apply to selected items"
    Then I should see "Delete the selected membership(s) was applied to 1 item."

    Examples:
      | title               | type       |
      | An owned collection | collection |
      | An owned solution   | solution   |
