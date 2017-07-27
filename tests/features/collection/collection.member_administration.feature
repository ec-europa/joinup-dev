@api @email
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
      | Username          | Roles | E-mail                        | First name | Family name |
      # Authenticated user.
      | Lisa Cuddy        |       | lisa_cuddy@example.com        | Lisa       | Cuddy       |
      | Gregory House     |       | gregory_house@example.com     | Gregory    | House       |
      | Kathie Cumbershot |       | kathie_cumbershot@example.com | Kathie     | Cumbershot  |
      | Donald Duck       |       | donald_duck@example.com       | Donald     | Duck        |
    And the following collections:
      | title             | description               | logo     | banner     | owner        | contact information                    | closed | state     |
      | Medical diagnosis | 10 patients in 10 minutes | logo.png | banner.jpg | James Wilson | Princeton-Plainsboro Teaching Hospital | yes    | validated |
    And the following collection user memberships:
      | collection        | user              | roles                      | state   |
      | Medical diagnosis | Lisa Cuddy        | administrator, facilitator | active  |
      | Medical diagnosis | Gregory House     |                            | active  |
      | Medical diagnosis | Kathie Cumbershot |                            | pending |

  Scenario: Request a membership
    When I am logged in as "Donald Duck"
    And all e-mails have been sent
    And I go to the "Medical diagnosis" collection
    And I press the "Join this collection" button
    Then I should see the success message "Your membership to the Medical diagnosis collection is under approval."
    And the following email should have been sent:
      | recipient | Lisa Cuddy                                                                                                                     |
      | subject   | Joinup: A user has requested to join your collection                                                                           |
      | body      | Donald Duck has requested to join your collection "Medical diagnosis" as a member. To approve or reject this request, click on |

  Scenario: Approve a membership
    # Check that a member with pending state does not have access to add new content.
    When I am logged in as "Kathie Cumbershot"
    And I go to the "Medical diagnosis" collection
    Then I should not see the plus button menu
    And I should not see the link "Add news"

    # Approve a membership.
    When I am logged in as "Lisa Cuddy"
    And all e-mails have been sent
    And I go to the "Medical diagnosis" collection
    Then I click "Members" in the "Left sidebar"
    # Assert that the user does not see the default OG tab.
    Then I should not see the link "Group"
    And I check the box "Update the member Kathie Cumbershot"
    Then I select "Approve the pending membership(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the following success messages:
      | Approve the pending membership(s) was applied to 1 item. |
    And the following email should have been sent:
      | recipient | Kathie Cumbershot                                                               |
      | subject   | Joinup: Your request to join the collection Medical diagnosis was approved      |
      | body      | Lisa Cuddy has approved your request to join the "Medical diagnosis" collection |

    # Check new privileges.
    When I am logged in as "Kathie Cumbershot"
    And I go to the "Medical diagnosis" collection
    # Check that I see one of the random links that requires an active membership.
    Then I should see the plus button menu
    Then I should see the link "Add news"

  Scenario: Reject a membership
    When I am logged in as "Lisa Cuddy"
    And all e-mails have been sent
    And I go to the "Medical diagnosis" collection
    Then I click "Members" in the "Left sidebar"
    # Assert that the user does not see the default OG tab.
    Then I should not see the link "Group"
    And I check the box "Update the member Kathie Cumbershot"
    Then I select "Delete the selected membership(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the following success messages:
      | Delete the selected membership(s) was applied to 1 item. |
    And the following email should have been sent:
      | recipient | Kathie Cumbershot                                                               |
      | subject   | Joinup: Your request to join the collection Medical diagnosis was rejected      |
      | body      | Lisa Cuddy has rejected your request to join the "Medical diagnosis" collection |

    # Check new privileges.
    When I am logged in as "Kathie Cumbershot"
    And I go to the "Medical diagnosis" collection
    # Check that I see one of the random links that requires an active membership.
    Then I should not see the plus button menu
    And I should see the button "Join this collection"

  Scenario: Assign a new role to a member
    # Check that Dr House can't edit the collection.
    When I am logged in as "Gregory House"
    And I go to the "Medical diagnosis" collection
    Then I go to the "Medical diagnosis" collection edit form
    Then I should see the heading "Access denied"

    # Dr Cuddy promotes Dr House to facilitator.
    When I am logged in as "Lisa Cuddy"
    And I go to the "Medical diagnosis" collection
    Then I click "Members" in the "Left sidebar"
    # Assert that the user does not see the default OG tab.
    Then I should not see the link "Group"
    Then I check the box "Update the member Gregory House"
    Then I select "Add the Collection facilitator role to the selected members" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the following success messages:
      | Add the Collection facilitator role to the selected members was applied to 1 item. |

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
