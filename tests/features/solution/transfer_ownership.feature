@api
Feature: As a solution owner
  In order to manage my solutions
  I should be able to transfer the solution ownership.

  Background:
    Given users:
      | Username | Roles                   | Password |
      | loner    |                         | Pass |
      | happy    | administrator,moderator | Pass |
      | cruel    |                         | Pass |
      | shy      |                         | Pass |
      | light    | moderator               | Pass |
      | frozen   |                         | Pass |
    And the following solution:
      | title | Learn German in 1 Month |
      | state | validated               |
    And the following solution user memberships:
      | solution                | user   | roles       |
      | Learn German in 1 Month | loner  |             |
      | Learn German in 1 Month | cruel  | owner       |
      | Learn German in 1 Month | shy    | facilitator |
      | Learn German in 1 Month | frozen | owner       |

  Scenario Outline: Administrators, moderators and owners can transfer the solution ownership.
    Given I am logged in as "<user>"
    And I go to the homepage of the "Learn German in 1 Month" solution
    And I click "Members"

    # Try to transfer the ownership to the current owner.
    Given I select the "cruel" row
    And I select "Transfer the ownership of the solution to the selected member" from "Action"
    When I press "Apply to selected items"
    Then I should see "Member cruel is already the owner of Learn German in 1 Month solution. Please select other user."

    # Try to transfer the ownership to multiple users.
    Given I select the "loner" row
    And I select the "shy" row
    And I select "Transfer the ownership of the solution to the selected member" from "Action"
    When I press "Apply to selected items"
    Then I should see "You cannot transfer the solution ownership to more than one user. Please select a single user."

    Given I select the "shy" row
    And I select "Transfer the ownership of the solution to the selected member" from "Action"
    When I press "Apply to selected items"
    Then I should see "Are you sure you want to transfer the ownership of Learn German in 1 Month solution to shy?"

    When I press "Confirm"
    Then I should see "Ownership of Learn German in 1 Month solution transferred from users cruel, frozen to shy."
    And I should see the text "Solution owner" in the "shy" row
    # Because the 'happy' user is granted with the site-wide permission
    # 'administer solution ownership', he is not dependent on the ownership
    # changes within the solution, thus he's able to manage the solution
    # ownership again. But the user 'cruel' cannot access anymore the option to
    # manage the ownership because, by transferring its ownership, he has locked
    # out himself from administering the solution ownership.
    And the "Action" field should <option exists> the "Transfer the ownership of the solution to the selected member" option
    And I should not see the text "Solution owner" in the "cruel" row
    # The former owners are receiving, in compensation, the facilitator role.
    But I should see the text "Solution facilitator" in the "cruel" row
    And I should not see the text "Solution owner" in the "frozen" row
    # The former owners are receiving, in compensation, the facilitator role.
    But I should see the text "Solution facilitator" in the "frozen" row

    Examples:
      | user  | option exists |
      | happy | contain       |
      | light | contain       |
      | cruel | not contain   |

  Scenario: Solution facilitators do not have access to transfer ownership.
    Given I am logged in as "shy"
    And I go to the homepage of the "Learn German in 1 Month" solution
    Given I click "Members"
    Then the available options in the "Action" select should not include the "Transfer the ownership of the solution to the selected member" options

  Scenario: Collection facilitators cannot transfer ownership of a solution.
    Given the following collection:
      | title | Babylon   |
      | state | validated |
    Given collection user membership:
      | collection | user  | roles       |
      | Babylon    | loner |             |
      | Babylon    | shy   | facilitator |

    Given I am logged in as "shy"
    And I go to the homepage of the "Babylon" collection
    Given I click "Members"
    Then the available options in the "Action" select should not include the "Transfer the ownership of the solution to the selected member" options
