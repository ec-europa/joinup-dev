@api
Feature: As a group (collection or solution) owner or site moderator
  In order to manage my group
  I should be able to transfer the group ownership.

  Background:
    Given users:
      | Username | Roles                   | Password |
      | loner    |                         | Pass     |
      | happy    | administrator,moderator | Pass     |
      | cruel    |                         | Pass     |
      | shy      |                         | Pass     |
      | light    | moderator               | Pass     |
      | frozen   |                         | Pass     |
    And the following collection:
      | title | Intensive Language Learning |
      | state | validated                   |
    And the following collection user memberships:
      | collection                  | user   | roles       |
      | Intensive Language Learning | loner  |             |
      | Intensive Language Learning | cruel  | owner       |
      | Intensive Language Learning | shy    | facilitator |
      | Intensive Language Learning | frozen | owner       |
    And the following solution:
      | title | Learn German in 1 Month |
      | state | validated               |
    And the following solution user memberships:
      | solution                | user   | roles       |
      | Learn German in 1 Month | loner  |             |
      | Learn German in 1 Month | cruel  | owner       |
      | Learn German in 1 Month | shy    | facilitator |
      | Learn German in 1 Month | frozen | owner       |

  Scenario Outline: Administrators, moderators and owners can transfer the group ownership.
    Given I am logged in as "<user>"
    And I go to the homepage of the "<title>" <type>
    And I click "Members"

    # Try to transfer the ownership to the current owner.
    Given I select the "cruel" row
    And I select "Transfer the ownership of the <type> to the selected member" from "Action"
    When I press "Apply to selected items"
    Then I should see "Member cruel is already the owner of <title> <type>. Please select other user."

    # Try to transfer the ownership to multiple users.
    Given I select the "loner" row
    And I select the "shy" row
    And I select "Transfer the ownership of the <type> to the selected member" from "Action"
    When I press "Apply to selected items"
    Then I should see "You cannot transfer the <type> ownership to more than one user. Please select a single user."

    Given I select the "shy" row
    And I select "Transfer the ownership of the <type> to the selected member" from "Action"
    When I press "Apply to selected items"
    Then I should see "Are you sure you want to transfer the ownership of <title> <type> to shy?"

    When I press "Confirm"
    Then I should see "Ownership of <title> <type> transferred from users cruel, frozen to shy."
    And I should see the text "<type capitalized> owner" in the "shy" row
    # Because the 'happy' user is granted with the site-wide permission
    # ('administer {group} ownership'), he is not dependent on the ownership
    # changes within the group, thus he's able to manage the group ownership
    # again. But the user 'cruel' cannot access anymore the option to manage the
    # ownership because, by transferring its ownership, he has locked out
    # himself from administering the group ownership.
    And the "Action" field should <option exists> the "Transfer the ownership of the <type> to the selected member" option
    And I should not see the text "<type capitalized> owner" in the "cruel" row
    # The former owners are receiving, in compensation, the facilitator role.
    But I should see the text "<type capitalized> facilitator" in the "cruel" row
    And I should not see the text "<type capitalized> owner" in the "frozen" row
    # The former owners are receiving, in compensation, the facilitator role.
    But I should see the text "<type capitalized> facilitator" in the "frozen" row

    Examples:
      | user  | option exists | type       | type capitalized | title                       |
      | happy | contain       | collection | Collection       | Intensive Language Learning |
      | light | contain       | collection | Collection       | Intensive Language Learning |
      | cruel | not contain   | collection | Collection       | Intensive Language Learning |
      | happy | contain       | solution   | Solution         | Learn German in 1 Month     |
      | light | contain       | solution   | Solution         | Learn German in 1 Month     |
      | cruel | not contain   | solution   | Solution         | Learn German in 1 Month     |

  Scenario Outline: Group facilitators do not have access to transfer ownership.
    Given I am logged in as "shy"
    And I go to the homepage of the "<title>" <type>
    Given I click "Members"
    Then the available options in the "Action" select should not include the "Transfer the ownership of the <type> to the selected member" options

    Examples:
      | type       | title                       |
      | collection | Intensive Language Learning |
      | solution   | Learn German in 1 Month     |

  Scenario: Collection owner cannot transfer ownership of a child solution, neither viceversa.
    Given the following solution:
      | title | Rivers Of Babylon |
      | state | validated         |
    And solution user membership:
      | solution          | user  | roles       |
      | Rivers Of Babylon | loner | owner       |
      | Rivers Of Babylon | shy   | facilitator |
    Given the following collection:
      | title      | Babylon           |
      | state      | validated         |
      | affiliates | Rivers Of Babylon |
    And collection user membership:
      | collection | user  | roles       |
      | Babylon    | shy   | owner       |
      | Babylon    | loner | facilitator |

    Given I am logged in as "shy"
    And I go to the homepage of the "Rivers Of Babylon" solution
    Given I click "Members"
    Then the available options in the "Action" select should not include the "Transfer the ownership of the solution to the selected member" options

    Given I am logged in as "loner"
    And I go to the homepage of the "Babylon" collection
    Given I click "Members"
    Then the available options in the "Action" select should not include the "Transfer the ownership of the solution to the selected member" options
