@api
Feature: Collection membership administration
  In order to build a community
  As a collection facilitator
  I need to be able to manager collection members

  Scenario: Assign a new role to a member
    Given the following owner:
      | name         |
      | James Wilson |
    And the following contact:
      | name  | Princeton-Plainsboro Teaching Hospital |
      | email | info@princeton-plainsboro.us           |
    And users:
      | name          | roles |
      # Authenticated user.
      | Lisa Cuddy    |       |
      | Gregory House |       |
    And the following collections:
      | title             | description               | logo     | banner     | owner        | contact information                    | state     |
      | Medical diagnosis | 10 patients in 10 minutes | logo.png | banner.jpg | James Wilson | Princeton-Plainsboro Teaching Hospital | validated |

    And the following collection user memberships:
      | collection        | user          | roles       |
      | Medical diagnosis | Lisa Cuddy    | facilitator |
      | Medical diagnosis | Gregory House |             |

    # Check that Dr House can't edit the collection.
    When I am logged in as "Gregory House"
    And I go to the "Medical diagnosis" collection
    Then I go to the "Medical diagnosis" collection edit form
    Then I should see the heading "Access denied"

    # Dr Cuddy promotes Dr House to facilitator.
    When I am logged in as "Lisa Cuddy"
    And I go to the "Medical diagnosis" collection
    Then I click "Members" in the "Entity actions"
    # Assert that the user does not see the default OG tab.
    Then I should not see the link "Group" in the "Entity actions"
    Then I check the box "Update the member Gregory House"
    Then I select "Add the Collection facilitator role to the selected members" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the following success messages:
      |Add the Collection facilitator role to the selected members was applied to 1 item.|

    # Dr House can now edit the collection.
    When I am logged in as "Gregory House"
    And I go to the "Medical diagnosis" collection
    Then I go to the "Medical diagnosis" collection edit form
    Then I should not see the heading "Access denied"
