# @todo: Will be re enabled in ISAICP-2369.
# If you want to run this test through Selenium, uncomment the specified lines!
# @api
#  Scenario: Add a link to og menu
#    Given collections:
#      | title              | description                     |
#      | Lord of the rings  | A collection for true LOTR fans |
#      | Star Trek          | A collection for Trekkies       |
#    And users:
#      | name      | roles      |
#      | Gandalf   | moderator  |
#    # No links in new menu
#    Given I am logged in as "Gandalf"
#    When I go to the homepage of the "Lord of the rings" collection
#    Then I should see the link "Add menu"
#    When I click "Add menu"
#    Then I should see "There are no menu links yet."
#    # Add first link.
#    When I click "Add link"
#    And I fill in "Menu link title" with "Mines of Moria"
#    And I fill in "Link" with "/user"
#    Then I press the "Save" button
#    Then I should see the success message "The menu link has been saved."
#    # Add second link.
#    When I click "Add link"
#    And I fill in "Menu link title" with "Mordor"
#    And I fill in "Link" with "/user"
#    Then I press the "Save" button
#    Then I should see the success message "The menu link has been saved."
#
#    # Check for the link on the collection
#    When I go to the homepage of the "Lord of the rings" collection
#    # Check order of links.
#    Then I should see the following collection menu items in the specified order:
#      | text           |
#      | Mines of Moria |
#      | Mordor         |
#
#    # Edit menu and change the order of items.
#    # When you hover over the menu, you will notice a little pencil icon
#    # appearing at the end right of the region. Click on this icon to show the
#    # "Edit menu" link.
#    Then I click the contextual link "Edit menu" in the "Left sidebar" region
#    # To run on Selenium, uncomment next line:
#    # Then I press the "Show row weights" button
#    Then I select "5" from "Weight for Mines of Moria"
#    Then I press the "Save" button
#
#    # Assert the changed order
#    Then I should see the following collection menu items in the specified order:
#      | text           |
#      | Mordor         |
#      | Mines of Moria |
#
#    # Assert link is not on other collection pages
#    When I go to the homepage of the "Star Trek" collection
#    Then I should not see the link "Mines of Moria"
#    Then I should not see the link "Mordor"
