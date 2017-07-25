@api
Feature: Collection membership administration
  In order to build a community
  As a collection facilitator
  I need to be able to manager collection members

  Background:
    Given the following owner:
      | name         |
      | James Wilson |
    And the following contact:
      | name  | Princeton-Plainsboro Teaching Hospital |
      | email | info@princeton-plainsboro.us           |
    And users:
      | Username      | Roles | E-mail                    | First name | Family name |
      # Authenticated user.
      | Lisa Cuddy    |       | lisa_cuddy@example.com    | Lisa       | Cuddy       |
      | Gregory House |       | gregory_house@example.com | Gregory    | House       |
    And the following collections:
      | title             | description               | logo     | banner     | owner        | contact information                    | state     |
      | Medical diagnosis | 10 patients in 10 minutes | logo.png | banner.jpg | James Wilson | Princeton-Plainsboro Teaching Hospital | validated |
    And the following collection user memberships:
      | collection        | user          | roles       |
      | Medical diagnosis | Lisa Cuddy    | facilitator |
      | Medical diagnosis | Gregory House |             |

  @email
  Scenario: Assign a new role to a member
    # Check that Dr House can't edit the collection.
    When I am logged in as "Gregory House"
    And I go to the "Medical diagnosis" collection
    Then I go to the "Medical diagnosis" collection edit form
    Then I should see the heading "Access denied"

    # Dr Cuddy promotes Dr House to facilitator.
    When I am logged in as "Lisa Cuddy"
    And all e-mails have been sent
    And I go to the "Medical diagnosis" collection
    Then I click "Members" in the "Left sidebar"
    # Assert that the user does not see the default OG tab.
    Then I should not see the link "Group"
    Then I check the box "Update the member Gregory House"
    Then I select "Add the Collection facilitator role to the selected members" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the following success messages:
      | Add the Collection facilitator role to the selected members was applied to 1 item. |
    And the following system email should have been sent:
      | recipient | Gregory House                                                                                 |
      | subject   | Your role has been change to Medical diagnosis                                                |
      | body      | A collection moderator has changed your role in this group to Member, Collection facilitator. |

    # Dr House can now edit the collection.
    When I am logged in as "Gregory House"
    And I go to the "Medical diagnosis" collection
    Then I go to the "Medical diagnosis" collection edit form
    Then I should not see the heading "Access denied"

  Scenario: Only privileged members should be able to add facilitators
    When I am not logged in
    And I go to the "Medical diagnosis" collection
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add facilitators"

    When I am logged in as an authenticated
    And I go to the "Medical diagnosis" collection
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add facilitators"

    When I am logged in as "Gregory House"
    And I go to the "Medical diagnosis" collection
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add facilitators"

    When I am logged in as "Lisa Cuddy"
    And I go to the "Medical diagnosis" collection
    And I click "Members" in the "Left sidebar"
    Then I should see the link "Add facilitators"

    # Add a facilitator.
    When I click "Add facilitators"
    And I fill in "Email or name" with "gregory_house@example.com"
    And I press "Filter"
    Then I should see the text "Gregory House (gregory_house@example.com)"
    When I check "Gregory House (gregory_house@example.com)"
    And I press "Add facilitators"
    # Submitting the form takes us back to the "Members" page.
    Then I should see the heading "Members"

    # Try new privileges.
    When I am logged in as "Gregory House"
    And I go to the "Medical diagnosis" collection
    And I click "Members" in the "Left sidebar"
    Then I should see the link "Add facilitators"
    When I click "Add facilitators"
    Then I should see the heading "Add facilitators"
