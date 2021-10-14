@api @group-e
Feature: Collection membership overview
  In order to foster my community and create a sense of belonging
  As a collection member
  I need to be able to see an overview of my fellow members

  Scenario: Show the collection members as a list of tiles
    Given the following owner:
      | name           |
      | Ayodele Sommer |
    And the following contact:
      | name  | Nita Yang             |
      | email | supernita@yahoo.co.uk |
    And users:
      # We're adding many users so we can test the different roles and states,
      #  as well as the pager. 12 users are shown per page.
      | Username              | First name | Family name | Photo        | Business title                  |
      | Ruby Valenta          | Ruby       | Robert      | leonardo.jpg | Chairman                        |
      | Bohumil Unterbrink    | Bohumil    | Unterbrink  | ada.png      | Senior Executive Vice President |
      | Isabell Zahariev      | Isabell    | Zahariev    | charles.jpg  | Chief Executive Officer         |
      | Gemma Hackett         | Gemma      | Hackett     | tim.jpg      | President                       |
      | Delicia Hart Val      | Delicia    | Hart        | alan.jpg     | Executive Director              |
      | Sukhrab Valenta       | Sukhrab    | Valenta     | linus.jpeg   | Managing Director               |
      | Jun Schrader          | Jun        | Schrader    | blaise.jpg   | General Manager                 |
      | Ingibjörg De Snaaijer | Ingibjörg  | De Snaaijer | richard.jpg  | Department Head                 |
      | Suk Karpáti           | Suk        | Karpáti     | leonardo.jpg | Deputy General Manager          |
      | Janna Miller          | Janna      | Miller      | ada.png      | Assistant Manager               |
      | Lisa Miller           | Lisa       | Miller      | charles.jpg  | Chairman of the Board           |
      | Kendall Miller        | Kendall    | Miller      | tim.jpg      | Chief of Staff                  |
      | Kamil Napoleonis      | Kamil      | Napoleonis  | alan.jpg     | Commissioner                    |
      | Law Atteberry         | Law        | Atteberry   | linus.jpeg   | Comptroller                     |
      | Aniruddha Kováts      | Aniruddha  | Kováts      | blaise.jpg   | Chief Communications Officer    |
      | Aali Dalton           | Aali       | Dalton      | richard.jpg  | Founder                         |
    And the following collections:
      | title           | description        | logo     | banner     | owner          | contact information | state     |
      | Jubilant Robots | Fresh oil harvest! | logo.png | banner.jpg | Ayodele Sommer | Nita Yang           | validated |
    And the following collection user memberships:
      | collection      | user                  | roles       | state   |
      | Jubilant Robots | Ruby Valenta          | owner       |         |
      | Jubilant Robots | Bohumil Unterbrink    | facilitator |         |
      | Jubilant Robots | Isabell Zahariev      |             | blocked |
      | Jubilant Robots | Gemma Hackett         |             | pending |
      | Jubilant Robots | Delicia Hart Val      | facilitator |         |
      | Jubilant Robots | Sukhrab Valenta       | facilitator |         |
      | Jubilant Robots | Jun Schrader          |             |         |
      | Jubilant Robots | Ingibjörg De Snaaijer |             |         |
      | Jubilant Robots | Suk Karpáti           |             |         |
      | Jubilant Robots | Janna Miller          |             |         |
      | Jubilant Robots | Lisa Miller           |             |         |
      | Jubilant Robots | Kendall Miller        |             |         |
      | Jubilant Robots | Kamil Napoleonis      |             |         |
      | Jubilant Robots | Law Atteberry         |             |         |
      | Jubilant Robots | Aniruddha Kováts      |             |         |
      | Jubilant Robots | Aali Dalton           |             |         |

    # The membership overview should be accessible for anonymous users.
    When I am not logged in
    And I go to the "Jubilant Robots" collection
    Then I should see the link "Members" in the "Left sidebar"

    # The first 12 active members should be shown, ordered by first name - last name.
    When I click "Members"
    Then I should see the heading "Members"

    # Check that clean URLs are being applied to the "members" subpage.
    And I should be on "/collection/jubilant-robots/members"

    And I should see the following tiles in the correct order:
      | Aali Dalton           |
      | Aniruddha Kováts      |
      | Bohumil Unterbrink    |
      | Delicia Hart          |
      | Ingibjörg De Snaaijer |
      | Janna Miller          |
      | Jun Schrader          |
      | Kamil Napoleonis      |
      | Kendall Miller        |
      | Law Atteberry         |
      | Lisa Miller           |
      | Ruby Robert           |
    # The 13th and 14th member should not be visible on this page, but on the next page.
    And I should not see the "Suk Karpáti" tile
    And I should not see the "Sukhrab Valenta" tile
    # A blocked member should not be visible.
    And I should not see the "Isabell Zahariev" tile
    # A pending member should not be visible.
    And I should not see the "Gemma Hackett" tile

    # Navigate to the next page and check that the 13th member is now visible.
    When I click "Next page"
    Then I should see the "Suk Karpáti" tile
    And I should see the "Sukhrab Valenta" tile

    # Check the filter on the user roles inside the collection.
    And the available options in the "Roles" select should be "- Any - (14), Author (0), Facilitator (4), Owner (1)"
    And the option "- Any - (14)" should be selected

    When I select "Owner (1)" from "Roles"
    And I press "Apply"
    And I should see the following tiles in the correct order:
      | Ruby Robert |

    When I select "Facilitator (4)" from "Roles"
    And I press "Apply"
    And I should see the following tiles in the correct order:
      | Bohumil Unterbrink |
      | Delicia Hart       |
      | Ruby Robert        |
      | Sukhrab Valenta    |

    When I fill in "Type something to filter the list" with "val"
    And I press "Apply"
    Then I should see the following tiles in the correct order:
      | Sukhrab Valenta |

    # Clicking the user name should lead to the user profile page.
    When I click "Sukhrab Valenta"
    Then I should see the heading "Sukhrab Valenta"
