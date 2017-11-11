@api
Feature: Tests the solution ownership transfer.

  Background:

    Given users:
      | Username | Roles                   |
      | loner    |                         |
      | happy    | administrator,moderator |
      | cruel    |                         |
      | shy      |                         |
      | light    | moderator               |
      | frozen   |                         |
    And the following solution:
      | title | Learn German in 1 Month |
      | state | validated               |
    And the following solution user memberships:
      | solution                | user   | roles       |
      | Learn German in 1 Month | loner  |             |
      | Learn German in 1 Month | cruel  | owner       |
      | Learn German in 1 Month | shy    | facilitator |
      | Learn German in 1 Month | frozen | owner       |

  Scenario Outline: As a site-wide administrator or as a solution owner when I
    manage the solution members, I am able to transfer the solution ownership.

    Given I am logged in as "<user>"
    And I go to the homepage of the "Learn German in 1 Month" solution
    And I click "Members"

    # Try to transfer the ownership to the current owner.
    Given I select the "cruel" row
    And I select "Transfer the ownership of the solution to the selected member" from "Action"
    When I press "Apply to selected items"
#But I take a screenshot
#    Then I should see "Member cruel is already the owner of Learn German in 1 Month solution. Please select other user."

    # Try to transfer the ownership to multiple users.
    Given I select the "loner" row
    And I select the "shy" row
    And I select "Transfer the ownership of the solution to the selected member" from "Action"
    When I press "Apply to selected items"
    Then I should see "You cannot transfer the solution ownership to more than one user. Please select a single user."
#
#    Given I select the "shy" row
#    And I select "Transfer the ownership of the solution to the selected member" from "Action"
#    When I press "Apply to selected items"
#    Then I should see "Are you sure you want to transfer the ownership of Learn German in 1 Month solution to shy?"
#
#    When I press "Confirm"
#    Then I should see "Ownership of Learn German in 1 Month solution transferred from users cruel, frozen to shy."
#    And I should see the text "Solution owner" in the "shy" row
#    # Because the 'happy' user is granted with the site-wide permission
#    # 'administer solution ownership', he's able to manage the solution
#    # ownership. User 'cruel' has managed the ownership because he was owner of
#    # this solution bus as he lost the ownership, he cannot access anymore the
#    # option to manage the ownership. He has locked out himself from
#    # administering solution ownership.
#    And the "Action" field should <option_present> the "Transfer the ownership of the solution to the selected member" option
#    And I should not see the text "Solution owner" in the "cruel" row
#    # The former owners are receiving, in compensation, the facilitator role.
#    But I should see the text "Solution facilitator" in the "cruel" row
#    And I should not see the text "Solution owner" in the "frozen" row
#    # The former owners are receiving, in compensation, the facilitator role.
#    But I should see the text "Solution facilitator" in the "frozen" row

    Examples:
      | user  | option_present |
      | happy | contain        |
#      | cruel | not contain    |

#  Scenario Outline: As a site-wide moderator or as solution facilitator (but not
#    owner), when I manage the solution members, I am not able to transfer the
#    solution ownership.
#
#    Given I am logged in as "<user>"
#    And I go to the homepage of the "Learn German in 1 Month" solution
#    Given I click "Members"
#    Then the available options in the "Action" select should not include the "Transfer the ownership of the solution to the selected member" options
#
#    Examples:
#      | user  |
#      | light |
#      | shy   |
#
#  Scenario: As a collection facilitator, when I manage the collection's members,
#    I'm not able to transfer the collection ownership.
#
#    Given the following collection:
#      | title | Babylon   |
#      | state | validated |
#    Given collection user membership:
#      | collection | user  | roles       |
#      | Babylon    | loner |             |
#      | Babylon    | shy   | facilitator |
#
#    Given I am logged in as "shy"
#    And I go to the homepage of the "Babylon" collection
#    Given I click "Members"
#    Then the available options in the "Action" select should not include the "Transfer the ownership of the solution to the selected member" options
