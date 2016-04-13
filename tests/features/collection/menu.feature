@api
Feature: Collections menu

  Scenario: Add a link to og menu
    Given collections:
      | name            | description            |
      | MenuCollection1 | First menu collection  |
      | MenuCollection2 | Second menu collection |
    And users:
      | title   | roles     |
      | Mr Menu | moderator |
    # Create a link
    Given I am logged in as "Mr Menu"
    When I go to the homepage of the "MenuCollection1" collection
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
