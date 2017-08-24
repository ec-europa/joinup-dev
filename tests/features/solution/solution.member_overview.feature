@api
Feature: Solution membership overview
  In order to foster my community and create a sense of belonging
  As a solution member
  I need to be able to see an overview of my fellow members

  Scenario: Show the solution members as a list of tiles
    Given users:
      # We're adding enough users so we can test the different roles and states
      # as well as the pager. 12 users are shown per page.
      | Username            | First name | Family name | Photo        | Business title                  |
      | Ariadna Astrauskas  | Ariadna    | Astrauskas  | leonardo.jpg | Chairman                        |
      | Fulvia Gabrielson   | Fulvia     | Gabrielson  | ada.png      | Senior Executive Vice President |
      | Ffransis Ecclestone | Ffransis   | Ecclestone  | charles.jpg  | Chief Executive Officer         |
      | Paulinho Jahoda     | Paulinho   | Jahoda      | tim.jpg      | President                       |
      | Aušra Buhr          | Aušra      | Buhr        | alan.jpg     | Executive Director              |
      | Gina Forney         | Gina       | Forney      | linus.jpeg   | Managing Director               |
      | Karna McReynolds    | Karna      | McReynolds  | blaise.jpg   | General Manager                 |
      | Dilek Bannister     | Dilek      | Bannister   | richard.jpg  | Department Head                 |
      | Fadl Sherman        | Fadl       | Sherman     | leonardo.jpg | Deputy General Manager          |
      | Badurad Nussenbaum  | Badurad    | Nussenbaum  | ada.png      | Assistant Manager               |
      | Mark Estévez        | Mark       | Estévez     | charles.jpg  | Chairman of the Board           |
      | Irini Prescott      | Irini      | Prescott    | tim.jpg      | Chief of Staff                  |
      | Peter Proudfoots    | Peter      | Proudfoots  | alan.jpg     | Commissioner                    |
      | Glædwine Ruskin     | Glædwine   | Ruskin      | linus.jpeg   | Comptroller                     |
      | Pocahontas Mathieu  | Pocahontas | Mathieu     | blaise.jpg   | Chief Communications Officer    |
      | Callista Wronski    | Callista   | Wronski     | richard.jpg  | Founder                         |
    And the following solution:
      | title       | Growing zone    |
      | description | Soil and gravel |
      | state       | validated       |
    And the following solution user memberships:
      | solution     | user                | roles       | state   |
      | Growing zone | Ariadna Astrauskas  | owner       |         |
      | Growing zone | Fulvia Gabrielson   | facilitator |         |
      | Growing zone | Ffransis Ecclestone |             | blocked |
      | Growing zone | Paulinho Jahoda     |             | pending |
      | Growing zone | Aušra Buhr          | facilitator |         |
      | Growing zone | Gina Forney         | facilitator |         |
      | Growing zone | Karna McReynolds    |             |         |
      | Growing zone | Dilek Bannister     |             |         |
      | Growing zone | Fadl Sherman        |             |         |
      | Growing zone | Badurad Nussenbaum  |             |         |
      | Growing zone | Mark Estévez        |             |         |
      | Growing zone | Irini Prescott      |             |         |
      | Growing zone | Peter Proudfoots    |             |         |
      | Growing zone | Glædwine Ruskin     |             |         |
      | Growing zone | Pocahontas Mathieu  |             |         |
      | Growing zone | Callista Wronski    |             |         |

    # The membership overview should be accessible for anonymous users.
    When I am not logged in
    And I go to the "Growing zone" solution
    Then I should see the link "Members" in the "Left sidebar"

    # The first 12 active members should be shown, ordered by first name - last name.
    When I click "Members"
    Then I should see the heading "Members"
    And I should see the following tiles in the correct order:
      | Ariadna Astrauskas  |
      | Aušra Buhr          |
      | Badurad Nussenbaum  |
      | Callista Wronski    |
      | Dilek Bannister     |
      | Fadl Sherman        |
      | Fulvia Gabrielson   |
      | Gina Forney         |
      | Glædwine Ruskin     |
      | Irini Prescott      |
      | Karna McReynolds    |
      | Mark Estévez        |
    # The 13th and 14th member should not be visible on this page, but on the next page.
    And I should not see the "Peter Proudfoots" tile
    And I should not see the "Pocahontas Mathieu" tile
    # A blocked member should not be visible.
    And I should not see the "Ffransis Ecclestone" tile
    # A pending member should not be visible.
    And I should not see the "Paulinho Jahoda" tile

    # Navigate to the next page and check that the remaining members are now visible.
    When I click "››"
    Then I should see the "Peter Proudfoots" tile
    And I should see the "Pocahontas Mathieu" tile

    # Check the filter on the user roles inside the collection.
    And the available options in the "Roles" select should be "- Any - (14), Owner (1), Facilitator (4)"
    And the option "- Any - (14)" should be selected

    When I select "Owner (1)" from "Roles"
    And I press "Apply"
    And I should see the following tiles in the correct order:
      | Ariadna Astrauskas |

    When I select "Facilitator (4)" from "Roles"
    And I press "Apply"
    And I should see the following tiles in the correct order:
      | Ariadna Astrauskas |
      | Aušra Buhr         |
      | Fulvia Gabrielson  |
      | Gina Forney        |

    # Clicking the user name should lead to the user profile page.
    When I click "Ariadna Astrauskas"
    Then I should see the heading "Ariadna Astrauskas"
