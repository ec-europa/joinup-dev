@api @terms
Feature: Solution membership administration
  In order to manage a solution
  As a solution facilitator
  I need to be able to manage solution members

  Scenario: Only privileged members should be able to add facilitators
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
      | Marcia Garcia    |       | marcia_garcia@example.com    | Gregory    | Garcia      |
    And the following solutions:
      | title            | related solutions | description                      | documentation | moderation | logo     | banner     | policy domain | state     | solution type | owner                | contact information                      |
      | The Missing Sons |                   | Blazing fast segmetation faults. | text.pdf      | no         | logo.png | banner.jpg | Demography    | validated |               | James Wilson the 2nd | Princeton-Plainsboro Teaching University |
    And the following solution user memberships:
      | solution         | user             | roles       |
      | The Missing Sons | Guadalupe Norman | facilitator |
      | The Missing Sons | Marcia Garcia    |             |

    When I am not logged in
    And I go to the "The Missing Sons" solution
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add facilitators"

    When I am logged in as an authenticated
    And I go to the "The Missing Sons" solution
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add facilitators"

    When I am logged in as "Marcia Garcia"
    And I go to the "The Missing Sons" solution
    And I click "Members" in the "Left sidebar"
    Then I should not see the link "Add facilitators"

    When I am logged in as "Guadalupe Norman"
    And I go to the "The Missing Sons" solution
    And I click "Members" in the "Left sidebar"
    Then I should see the link "Add facilitators"

    # Add a facilitator.
    When I click "Add facilitators"
    And I fill in "Email or name" with "marcia_garcia@example.com"
    And I press "Filter"
    Then I should see the text "Marcia Garcia (marcia_garcia@example.com)"
    When I check "Marcia Garcia (marcia_garcia@example.com)"
    And I press "Add facilitators"
    # Submitting the form takes us back to the "Members" page.
    Then I should see the heading "Members"

    # Try new privileges.
    When I am logged in as "Marcia Garcia"
    And I go to the "The Missing Sons" solution
    And I click "Members" in the "Left sidebar"
    Then I should see the link "Add facilitators"
    When I click "Add facilitators"
    Then I should see the heading "Add facilitators"
