@api
Feature: Collections menu
  @javascript
  Scenario: Add a link to og menu
    Given collections:
      | name              | description            | owner   | uri                                 |
      | MenuCollection1   | First menu collection  |         | http://joinup.eu/collection/ogmenu1 |
      | MenuCollection2   | Second menu collection |         | http://joinup.eu/collection/ogmenu2 |
    And users:
      | name      | roles      |
      | Mr Menu   | moderator  |
    # Create a link
    Given I am logged in as "Mr Menu"
    When I go to the homepage of the "MenuCollection1" collection
    Then I break
    Then I should see the link "Add menu"
    When I click "Add menu"
    Then I should see "There are no menu links yet."
    When I click "Add link"
    And I fill in "Menu link title" with "My user account page for the collection menu"
    And I fill in "Link" with "/user"
    Then I press the "Save" button
    Then I should see the success message "The menu link has been saved."

    # Check for the link on the collection
    When I go to the homepage of the "MenuCollection1" collection
    Then I should see the link "My user account page for the collection menu"

    # Assert link is not on other collection pages
    When I go to the homepage of the "MenuCollection2" collection
    Then I should not see the link "My user account page for the collection menu"
