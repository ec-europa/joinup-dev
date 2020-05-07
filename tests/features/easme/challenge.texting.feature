@api
Feature:
  As a developer of the website
  In order to maintain a separate wording for the collection bundle
  I need to be able to ensure that the wording 'collection' is properly switched to 'challenge'.

  Scenario: Ensure tiles display the challenge keyword.
    Given the following collection:
      | title | Some entity |
      | state | validated   |
    When I am on the homepage
    And I click "Challenges"
    Then I should not see the text "collection" in the "Some entity" tile
    And I should see the text "challenge" in the "Some entity" tile

    When I am logged in as a moderator
    And I go to the "Some entity" collection edit form
    # In a non JS test, there are no hidden tabs so this ensures that the text 'collection' or 'Collection'
    # is not displayed anywhere in any field name, title or description.
    # Only the main section is checked because the behat ::getText() method on the whole page also retrieves some JSON
    # text data which contain the keyword 'collection'.
    Then I should not see the text "collection" in the "Content" region
    And I should not see the text "Collection" in the "Content" region
