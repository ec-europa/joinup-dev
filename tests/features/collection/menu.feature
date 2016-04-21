@api
Feature: Collections menu
  Scenario: Add a link to og menu
    Given collections:
      | name               | description                     | owner | uri                                  |
      | Lord of the rings  | A collection for true LOTR fans |       | http://joinup.eu/collection/lotr     |
      | Star Trek          | A collection for Trekkies       |       | http://joinup.eu/collection/startrek |
    And users:
      | name      | roles      |
      | Gandalf   | moderator  |
    # No links in new menu
    Given I am logged in as "Gandalf"
    When I go to the homepage of the "Lord of the rings" collection
    Then I should see the link "Add menu"
    When I click "Add menu"
    Then I should see "There are no menu links yet."
    # Add first link.
    When I click "Add link"
    And I fill in "Menu link title" with "Mines of Moria"
    And I fill in "Link" with "/user"
    Then I press the "Save" button
    Then I should see the success message "The menu link has been saved."
    # Add second link.
    When I click "Add link"
    And I fill in "Menu link title" with "Mordor"
    And I fill in "Link" with "/user"
    Then I press the "Save" button
    Then I should see the success message "The menu link has been saved."

    # Check for the link on the collection
    When I go to the homepage of the "Lord of the rings" collection
    # Check order of links.
    Then I should see the following in the repeated ".menu-item" element within the context of the ".block-og-menu ul.menu .menu-item" element:
      | text           |
      | Mines of Moria |
      | Mordor         |

    # Edit menu and change the order of items.
    Then I press "Open configuration options" in the "Primary menu" region
    Then I click "Edit menu"
    Then I press the "Show row weights" button
    Then I select "5" from "Weight for Mines of Moria"
    Then I press the "Save" button

    # Assert the changed order
    Then I should see the following in the repeated ".menu-item" element within the context of the ".block-og-menu ul.menu .menu-item" element:
      | text           |
      | Mordor         |
      | Mines of Moria |

    # Assert link is not on other collection pages
    When I go to the homepage of the "Star Trek" collection
    Then I should not see the link "Mines of Moria"
    Then I should not see the link "Mordor"
