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
