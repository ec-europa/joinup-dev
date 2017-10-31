@api @joinup_collection
Feature: Tests membership to Joinup collection.

  Background:
    Given the following collections:
      | title                   | state     |
      | An arbitrary collection | validated |

  Scenario: As a newly registered user I'm automatically member of the 'Joinup'
    collection and I cannot leave.

    Given I am logged in as a user with the member role of the "An arbitrary collection" collection
    Then I am member of "Joinup" collection

    And I go to the overview page of the "An arbitrary collection" collection
    Then I should see the link "Leave this collection"

    When I go to the overview page of the "Joinup" collection
    Then I should not see the link "Leave this collection"
    But I should see "You cannot leave the Joinup collection"

    When I am about to leave the "Joinup" collection
    Then I should get an access denied error

  Scenario: As a moderator I am able to revoke the membership of a user to any
    arbitrary collection except 'Joinup'.

    Given users:
      | Username | E-mail           |
      | joe      | joe@example.com  |
      | jane     | jane@example.com |
    And the following collection user membership:
      | collection              | user |
      | An arbitrary collection | joe  |
    Then user "joe" is member of "Joinup" collection
    And user "jane" is member of "Joinup" collection

    Given I am logged in as a user with the moderator role
    And I go to the overview page of the "An arbitrary collection" collection
    And I click "Members"
    And I check "edit-og-membership-bulk-form-0"
    And I select "Delete the selected membership(s)" from "Action"
    And I press "Apply to selected items"
    Then I should see "Delete the selected membership(s) was applied to 1 item."

    Given I go to the overview page of the "Joinup" collection
    And I click "Members"
    Then the available options in the "Action" select should not include the "Delete the selected membership(s)" options
