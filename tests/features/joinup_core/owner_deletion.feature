@api @group-d
Feature: Deletion of collection and solution owners
  As a site owner
  In order to avoid my groups becoming orphaned
  I should be able to prevent moderators from deleting all owners of a group.

  Scenario Outline: A privileged user cannot remove all owners of a group.
    Given users:
      | Username       | Roles     | First name | Family name |
      | Group owner 1  |           | Group      | owner 1     |
      | Group owner 2  |           | Group      | owner 2     |
      | Group member   |           | Group      | member      |
      | Site moderator | moderator | Site       | moderator   |
    And the following <type>:
      | title | An owned group |
      | state | validated           |
    And the following <type> user memberships:
      | <type>         | user          | roles |
      | An owned group | Group owner 1 | owner |
      | An owned group | Group owner 2 | owner |
      | An owned group | Group member  |       |
    And I am logged in as "Site moderator"
    And I am on the members page of "An owned group"
    And I select the "Group owner 1" row
    And I select the "Group owner 2" row

    # Try to delete all owners at once.
    And I select "Delete the selected membership(s)" from "Action"
    When I press "Apply to selected items"

    Then I should see the error message "You cannot delete the owner of a <type>."

    # Deleting owners when at least one remains is still possible.
    Given I deselect the "Group owner 1" row
    When I press "Apply to selected items"
    And I press "Confirm"

    Then I should see the success message "The member Group owner 2 has been deleted from the 'An owned group' <type>."

    # Action is properly interrupted even if other memberships are about to be deleted.
    Given I select the "Group owner 1" row
    And I select the "Group member" row
    And I select "Delete the selected membership(s)" from "Action"
    When I press "Apply to selected items"
    Then I should see the error message "You cannot delete the owner of a <type>."

    # Normal memberships can be deleted without an issue.
    Given I deselect the "Group owner 1" row
    And I select "Delete the selected membership(s)" from "Action"
    When I press "Apply to selected items"
    And I press "Confirm"
    Then I should see the success message "The member Group member has been deleted from the 'An owned group' <type>."

    Examples:
      | type       |
      | collection |
      | solution   |
