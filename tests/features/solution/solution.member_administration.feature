@api @terms
Feature: Solution membership administration
  In order to manage a solution
  As a solution facilitator
  I need to be able to manage solution members

  Background:
    Given the following owner:
      | name                 |
      | James Wilson the 2nd |
    And the following contact:
      | name  | Princeton-Plainsboro Teaching University |
      | email | info@princeton-plainsboro.edu            |
    And users:
      | Username         | Roles | E-mail                       | First name | Family name |
      # Authenticated user.
      | Guadalupe Norman |       | guadalupe_norman@example.com | Guadalupe  | Norman      |
      | Marcia Garcia    |       | marcia_garcia@example.com    | Marcia     | Garcia      |
    And the following solutions:
      | title            | related solutions | description                       | documentation | moderation | logo     | banner     | topic      | state     | solution type | owner                | contact information                      |
      | The Missing Sons |                   | Blazing fast segmentation faults. | text.pdf      | no         | logo.png | banner.jpg | Demography | validated |               | James Wilson the 2nd | Princeton-Plainsboro Teaching University |
    And the following solution user memberships:
      | solution         | user             | roles       |
      | The Missing Sons | Guadalupe Norman | facilitator |
      | The Missing Sons | Marcia Garcia    |             |

  Scenario: Only privileged members should be able to add members
    When I am not logged in
    And I go to the members page of "The Missing Sons"
    Then I should not see the link "Add members"

    When I am logged in as an authenticated
    And I go to the members page of "The Missing Sons"
    Then I should not see the link "Add members"

    When I am logged in as "Marcia Garcia"
    And I go to the members page of "The Missing Sons"
    Then I should not see the link "Add members"

    When I am logged in as "Guadalupe Norman"
    And I go to the members page of "The Missing Sons"
    Then I should see the link "Add members"

    # Add a facilitator.
    When I click "Add members"
    Then the available options in the "Role" select should be "Member, Author, Facilitator"
    When I fill in "E-mail" with "marcia_garcia@example.com"
    And I press "Add"
    Then the page should show the following chips in the Content region:
      | Marcia Garcia |
    When I select "Facilitator" from "Role"
    And I press "Add members"
    # Submitting the form takes us back to the "Members" page.
    Then I should see the heading "Members"

    # Try new privileges.
    When I am logged in as "Marcia Garcia"
    And I go to the members page of "The Missing Sons"
    Then I should see the link "Add members"
    When I click "Add members"
    Then I should see the heading "Add members"

  Scenario: Assign and remove new role to a member
    When I am logged in as "Guadalupe Norman"
    And I go to the members page of "The Missing Sons"
    Then I should see the link "Add members"
    Then I check the box "Update the member Marcia Garcia"
    Then I select "Add the facilitator role to the selected members" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the following success messages:
      | success messages                                                        |
      | Add the facilitator role to the selected members was applied to 1 item. |
    And the following email should have been sent:
      | recipient | Marcia Garcia                                                                         |
      | subject   | Your role has been changed to facilitator                                             |
      | body      | Guadalupe Norman has changed your role in solution "The Missing Sons" to facilitator. |
    Then I check the box "Update the member Marcia Garcia"
    Then I select "Remove the facilitator role from the selected members" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the following success messages:
      | success messages                                                             |
      | Remove the facilitator role from the selected members was applied to 1 item. |
    And the following email should have been sent:
      | recipient | Marcia Garcia                                                                    |
      | subject   | Your role has been changed to member                                             |
      | body      | Guadalupe Norman has changed your role in solution "The Missing Sons" to member. |

  Scenario: Privileged members should be allowed to invite users to a solution.
    Given users:
      | Username  | E-mail                 | First name | Family name |
      | jbelanger | j.belanger@example.com | Jeannette  | Belanger    |
      | dwightone | dwight1@example.com    | Christian  | Dwight      |

    When I am not logged in
    And I go to the members page of "The Missing Sons"
    Then I should not see the link "Invite members"

    When I am logged in as an authenticated
    And I go to the members page of "The Missing Sons"
    Then I should not see the link "Invite members"

    When I am logged in as "dwightone"
    And I go to the members page of "The Missing Sons"
    Then I should not see the link "Invite members"

    When I am logged in as "Guadalupe Norman"
    And I go to the members page of "The Missing Sons"
    Then I should see the link "Invite members"
    When I click "Invite members"
    Then I should see the heading "Invite members"

    # Add a facilitator.
    When I fill in "E-mail" with "dwight1@example.com"
    And I press "Add"
    Then the page should show the following chips in the Content region:
      | Christian Dwight |
    When I select "Facilitator" from "Role"
    And the mail collector cache is empty
    And I press "Invite members"
    Then I should see the success message "1 user has been invited to this group."
    And the following email should have been sent:
      | recipient | dwightone                                                                                                  |
      | subject   | Invitation from Guadalupe Norman to join solution The Missing Sons.                                        |
      | body      | You have been invited by Guadalupe Norman to join the solution The Missing Sons as a solution facilitator. |

    # Accept the invitation directly.
    When I am logged in as "dwightone"
    And I accept the invitation for the "The Missing Sons" solution group
    And I go to the members page of "The Missing Sons"
    Then I should see the link "Add members"
    And I should see the link "Invite members"
    When I click "Invite members"
    Then I should see the heading "Invite members"
