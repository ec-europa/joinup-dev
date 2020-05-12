@api @group-b
Feature: Tests membership to Joinup collection.

  Background:
    Given the following collections:
      | title                   | state     |
      | An arbitrary collection | validated |

  Scenario: As a moderator I am able to revoke the membership of a user to any
  arbitrary challenge except 'Joinup'.

    Given users:
      | Username | E-mail           |
      | joe      | joe@example.com  |
      | jane     | jane@example.com |
    And the following collection user membership:
      | collection              | user |
      | An arbitrary collection | joe  |

    When I am logged in as a user with the moderator role
    And I go to the homepage of the "An arbitrary collection" collection
    And I click "Members"
    And I check "edit-og-membership-bulk-form-0"
    And I select "Delete the selected membership(s)" from "Action"

    When I press "Apply to selected items"
    Then I should see the heading "Are you sure you want to delete the selected membership from the 'An arbitrary collection' challenge?"
    And I should see "The member joe will be deleted from the 'An arbitrary collection' challenge."
    And I should see "This action cannot be undone."

    When I press "Confirm"
    Then I should see the success message "The member joe has been deleted from the 'An arbitrary collection' challenge."
