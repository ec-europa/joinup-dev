@api @terms @group-c
Feature: Collection homepage
  In order find content around a topic
  As a user of the website
  I need to be able see all content related to a collection on the collection homepage

  Background:
    Given users:
      | Username | Status | Roles     |
      | Frodo    | active |           |
      | Boromir  | active |           |
      | Legolas  | active |           |
      | Gimli    | active |           |
      | Samwise  | active | moderator |
    And the following owner:
      | name          |
      | Bilbo Baggins |
    And the following contact:
      | name  | Kalikatoura             |
      | email | kalikatoura@example.com |
    And the following collection:
      | title               | Middle earth daily               |
      | description         | Middle earth daily               |
      | owner               | Bilbo Baggins                    |
      | contact information | Kalikatoura                      |
      | logo                | logo.png                         |
      | moderation          | yes                              |
      | content creation    | members                          |
      | state               | validated                        |
      | topic               | Employment and Support Allowance |
    And the following solution:
      | title            | Bilbo's book          |
      | collection       | Middle earth daily    |
      | description      | Bilbo's autobiography |
      | content creation | registered users      |
      | creation date    | 2014-10-17 8:32am     |
      | state            | validated             |
    And the following collection user memberships:
      | collection         | user    | roles       |
      | Middle earth daily | Frodo   | facilitator |
      | Middle earth daily | Boromir |             |
      | Middle earth daily | Legolas |             |
    And news content:
      | title                                             | body                | topic             | collection         | state     | created           | changed  |
      | Rohirrim make extraordinary deal                  | Horse prices drops  | Finance in EU     | Middle earth daily | validated | 2014-10-17 8:34am | 2017-7-5 |
      | Breaking: Gandalf supposedly plans his retirement | A new white wizard? | Supplier exchange | Middle earth daily | validated | 2014-10-17 8:31am | 2017-7-5 |
    And event content:
      | title                                    | short title      | body                                      | collection         | created           | start date          | end date            | state     | topic             | changed  |
      | Big hobbit feast - fireworks at midnight | Big hobbit feast | Barbecue followed by dance and fireworks. | Middle earth daily | 2014-10-17 8:33am | 2016-03-15T11:12:12 | 2016-03-15T11:12:12 | validated | Supplier exchange | 2017-7-5 |

  @clearStaticCache
  Scenario: The collection homepage shows the collection metrics.
    When I go to the homepage of the "Middle earth daily" collection
    Then I see the text "3 Members" in the "Header" region
    And I see the text "1 Solution" in the "Header" region

    # Test caching of the metrics: Solutions.
    When I delete the "Bilbo's book" solution
    And I reload the page
    Then I see the text "3 Members" in the "Header" region
    And I see the text "0 Solutions" in the "Header" region

    When I delete the "Frodo" user
    And I reload the page
    Then I see the text "2 Members" in the "Header" region
    And I see the text "0 Solutions" in the "Header" region

  Scenario: The collection homepage is cached for anonymous users
    Given I am an anonymous user
    And I go to the homepage of the "Middle earth daily" collection
    Then the page should be cacheable
    When I reload the page
    Then the page should be cached

  Scenario Outline: The collection homepage is cached for authenticated users
    Given I am logged in as <user>
    And I go to the homepage of the "Middle earth daily" collection
    Then the page should be cacheable
    When I reload the page
    Then the page should be cached

    Examples:
      | user    |
      | Frodo   |
      | Boromir |
      | Gimli   |
      | Samwise |

  Scenario: The collection homepage shows related content.
    When I go to the homepage of the "Middle earth daily" collection
    # The collection fields are shown in the about page.
    Then I should not see the text "Only members can create new content"
    And I should not see the text "Moderated"
    And I should not see the text "Open collection"
    And I should not see the text "Bilbo Baggins"
    And I should not see the text "Employment and Support Allowance"
    # The collection content is shown here in the correct order.
    Then I should see the following tiles in the correct order:
      | Rohirrim make extraordinary deal                  |
      | Big hobbit feast - fireworks at midnight          |
      | Bilbo's book                                      |
      | Breaking: Gandalf supposedly plans his retirement |

    # Test that unrelated content does not show up in the tiles.
    And I should not see the "Bilbo Baggins" tile
    # Test that the collection itself does not show up in the tiles.
    And I should not see the "Middle earth daily" tile

    # Test the filtering by facets.
    When I click the Event content tab
    Then I should see the "Big hobbit feast - fireworks at midnight" tile
    But I should not see text matching "Rohirrim make extraordinary deal"
    And I should not see text matching "Breaking: Gandalf supposedly plans his retirement"
    When I click the News content tab
    Then I should not see text matching "Big hobbit feast - fireworks at midnight"
    But I should see the "Rohirrim make extraordinary deal" tile
    And I should see the "Breaking: Gandalf supposedly plans his retirement" tile

    # Deselect the content type filter.
    When I click the News content tab
    # Verify the topic inline facet.
    Then "all topics" should be selected in the "collection topic" inline facet
    And the "collection topic" inline facet should allow selecting the following values:
      | Supplier exchange (2) |
      | Finance in EU (1)     |

    When I click "Supplier exchange" in the "collection topic" inline facet
    Then "Supplier exchange (2)" should be selected in the "collection topic" inline facet
    And the "collection topic" inline facet should allow selecting the following values:
      | Finance in EU (1) |
      | all topics        |
    Then I should see the following tiles in the correct order:
      | Big hobbit feast - fireworks at midnight          |
      | Breaking: Gandalf supposedly plans his retirement |
    But I should not see the "Rohirrim make extraordinary deal" tile

    # Verify that the inline widget reset link doesn't break other active facets.
    When I click the News content tab
    Then "Supplier exchange (1)" should be selected in the "collection topic" inline facet
    And the "collection topic" inline facet should allow selecting the following values:
      | Finance in EU (1) |
      | all topics        |
    And I should see the "Breaking: Gandalf supposedly plans his retirement" tile
    But I should not see the "Big hobbit feast - fireworks at midnight" tile
    And I should not see the "Rohirrim make extraordinary deal" tile
    # Reset the topic selection.
    When I click "all topics" in the "collection topic" inline facet
    Then "all topics" should be selected in the "collection topic" inline facet
    And the "collection topic" inline facet should allow selecting the following values:
      | Finance in EU (1)     |
      | Supplier exchange (1) |
    And I should see the "Breaking: Gandalf supposedly plans his retirement" tile
    And I should see the "Rohirrim make extraordinary deal" tile
    But I should not see the "Big hobbit feast - fireworks at midnight" tile

  # Regression test to ensure that related community content does not appear in the draft view.
  # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3262
  Scenario: The related content should not be shown in the draft view version as part of the content.
    When I am logged in as a facilitator of the "Middle earth daily" collection
    And I go to the homepage of the "Middle earth daily" collection
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "Middle earth nightly"
    And I press "Save as draft"
    And I click "View draft" in the "Entity actions" region
    Then I should see the text "Moderated"
    And I should see the text "Open collection"
    And I should see the text "Only members can create content."
    And I should see the text "Bilbo Baggins"
    And I should see the text "Employment and Support Allowance"
    And I should see the heading "Middle earth nightly"
    # But the tiles should not be visible.
    But I should not see the "Rohirrim make extraordinary deal" tile
    And I should not see the "Breaking: Gandalf supposedly plans his retirement" tile
    And I should not see the "Big hobbit feast - fireworks at midnight" tile

  Scenario: The collection homepage should be cacheable for anonymous users.
    Given I am an anonymous user
    When I go to the homepage of the "Middle earth daily" collection
    Then the page should not be cached
    When I reload the page
    Then the page should be cached
