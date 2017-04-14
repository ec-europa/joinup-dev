@api
Feature: Collection homepage
  In order find content around a topic
  As a user of the website
  I need to be able see all content related to a collection on the collection homepage

  Scenario: The collection homepage shows related content
    Given the following owner:
      | name          |
      | Bilbo Baggins |
    And the following collection:
      | title             | Middle earth daily |
      | owner             | Bilbo Baggins      |
      | logo              | logo.png           |
      | moderation        | yes                |
      | elibrary creation | facilitators       |
      | state             | validated          |
    And news content:
      | title                                             | body                | collection         | status    |
      | Rohirrim make extraordinary deal                  | Horse prices drops  | Middle earth daily | published |
      | Breaking: Gandalf supposedly plans his retirement | A new white wizard? | Middle earth daily | published |
    And event content:
      | title                                    | short title      | body                                      | collection         | start date          |
      | Big hobbit feast - fireworks at midnight | Big hobbit feast | Barbecue followed by dance and fireworks. | Middle earth daily | 2016-03-15T11:12:12 |
    And I go to the homepage of the "Middle earth daily" collection
    Then I should see text matching "Rohirrim make extraordinary deal"
    Then I should see text matching "Breaking: Gandalf supposedly plans his retirement"
    Then I should see text matching "Big hobbit feast - fireworks at midnight"

    # Test that unrelated content does not show up in the tiles.
    And I should not see the "Bilbo Baggins" tile
    # Test that the collection itself does not show up in the tiles.
    And I should not see the "Middle earth daily" tile

    # Test the filtering by facets.
    When I click the Event content tab
    Then I should see text matching "Big hobbit feast - fireworks at midnight"
    Then I should not see text matching "Rohirrim make extraordinary deal"
    Then I should not see text matching "Breaking: Gandalf supposedly plans his retirement"
    When I click the News content tab
    Then I should not see text matching "Big hobbit feast - fireworks at midnight"
    Then I should see text matching "Rohirrim make extraordinary deal"
    Then I should see text matching "Breaking: Gandalf supposedly plans his retirement"
