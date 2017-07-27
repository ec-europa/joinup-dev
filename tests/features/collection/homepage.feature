@api @terms
Feature: Collection homepage
  In order find content around a topic
  As a user of the website
  I need to be able see all content related to a collection on the collection homepage

  Background:
    Given users:
      | Username |
      | Frodo    |
      | Boromir  |
      | Legoloas |
      | Gimli    |
    Given the following owner:
      | name          |
      | Bilbo Baggins |
    Given the following solution:
      | title             | Bilbo's book          |
      | description       | Bilbo's autobiography |
      | elibrary creation | members               |
      | state             | validated             |
    And the following collection:
      | title             | Middle earth daily               |
      | description       | Middle earth daily               |
      | owner             | Bilbo Baggins                    |
      | logo              | logo.png                         |
      | moderation        | yes                              |
      | elibrary creation | members                          |
      | state             | validated                        |
      | policy domain     | Employment and Support Allowance |
      | affiliates        | Bilbo's book                     |
    And the following collection user memberships:
      | collection         | user     | roles       |
      | Middle earth daily | Frodo    | facilitator |
      | Middle earth daily | Boromir  |             |
      | Middle earth daily | Legoloas |             |
    And news content:
      | title                                             | body                | policy domain     | collection         | state     | created           | changed  |
      | Rohirrim make extraordinary deal                  | Horse prices drops  | Finance in EU     | Middle earth daily | validated | 2014-10-17 8:00am | 2017-7-5 |
      | Breaking: Gandalf supposedly plans his retirement | A new white wizard? | Supplier exchange | Middle earth daily | validated | 2014-10-17 8:00am | 2017-7-5 |
    And event content:
      | title                                    | short title      | body                                      | collection         | start date          | end date            | state     | policy domain     | created           | changed  |
      | Big hobbit feast - fireworks at midnight | Big hobbit feast | Barbecue followed by dance and fireworks. | Middle earth daily | 2016-03-15T11:12:12 | 2016-03-15T11:12:12 | validated | Supplier exchange | 2014-10-17 8:00am | 2017-7-5 |

  Scenario: The collection homepage shows the collection metrics.
    When I go to the homepage of the "Middle earth daily" collection
    Then I see the text "3 Members" in the "Header" region
    Then I see the text "1 Solution" in the "Header" region
    Then I see the days passed since "2017-07-05"
    # Test caching of the metrics: Members.
    # Gimli is not a member yet.
    When I am logged in as Gimli
    And I go to the homepage of the "Middle earth daily" collection
    And I press the "Join this collection" button
    And I go to the homepage of the "Middle earth daily" collection
    Then I see the text "4 Members" in the "Header" region

    # see ISAICP-3599
    # Test caching of the metrics: Solutions.
#    Then I delete the "Bilbo's book" solution
#    When I am logged in as Gimli
#    And I go to the homepage of the "Middle earth daily" collection
#    Then I see the text "0 Solutions" in the "Header" region

    # Test last updated
#    Then I am logged in as "Frodo"
#    And I go to the homepage of the "Middle earth daily" collection
#    Then I click "Rohirrim make extraordinary deal"
#    And I click "Edit" in the "Entity actions" region
#    Then I press "Update"
#    And I go to the homepage of the "Middle earth daily" collection
#    And I should see the text "0 days ago"

  Scenario: The collection homepage shows related content.
    When I go to the homepage of the "Middle earth daily" collection
    # The collection fields are shown in the about page.
    Then I should not see the text "Only members can create new content"
    And I should not see the text "Moderated"
    And I should not see the text "Open collection"
    And I should not see the text "Bilbo Baggins"
    And I should not see the text "Employment and Support Allowance"
    # The collection content is shown here.
    And I should see the "Rohirrim make extraordinary deal" tile
    And I should see the "Breaking: Gandalf supposedly plans his retirement" tile
    And I should see the "Big hobbit feast - fireworks at midnight" tile

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
    # Verify the policy domain inline facet.
    Then "all policy domains" should be selected in the "collection policy domain" inline facet
    And the "collection policy domain" inline facet should allow selecting the following values "Supplier exchange (2), Finance in EU (1)"

    When I click "Supplier exchange" in the "collection policy domain" inline facet
    Then "Supplier exchange (2)" should be selected in the "collection policy domain" inline facet
    And the "collection policy domain" inline facet should allow selecting the following values "Finance in EU (1), all policy domains"
    And I should see the "Breaking: Gandalf supposedly plans his retirement" tile
    And I should see the "Big hobbit feast - fireworks at midnight" tile
    But I should not see the "Rohirrim make extraordinary deal" tile

    # Verify that the inline widget reset link doesn't break other active facets.
    When I click the News content tab
    Then "Supplier exchange (1)" should be selected in the "collection policy domain" inline facet
    And the "collection policy domain" inline facet should allow selecting the following values "Finance in EU (1), all policy domains"
    And I should see the "Breaking: Gandalf supposedly plans his retirement" tile
    But I should not see the "Big hobbit feast - fireworks at midnight" tile
    And I should not see the "Rohirrim make extraordinary deal" tile
    # Reset the policy domain selection.
    When I click "all policy domains" in the "collection policy domain" inline facet
    Then "all policy domains" should be selected in the "collection policy domain" inline facet
    And the "collection policy domain" inline facet should allow selecting the following values "Finance in EU (1), Supplier exchange (1)"
    And I should see the "Breaking: Gandalf supposedly plans his retirement" tile
    And I should see the "Rohirrim make extraordinary deal" tile
    But I should not see the "Big hobbit feast - fireworks at midnight" tile

  Scenario: Forward search facets to the search page (Advanced search)
    Given I go to the homepage of the "Middle earth daily" collection
    When I click the News content tab
    And I click "Supplier exchange" in the "collection policy domain" inline facet
    And I click "Advanced search"
    Then I should be on the search page
    Then the News content tab should be selected
    And "Middle earth daily" should be selected in the "from" inline facet
    And "Supplier exchange (1)" should be selected in the "policy domain" inline facet
    And I should see the "Breaking: Gandalf supposedly plans his retirement" tile
    But I should not see the "Rohirrim make extraordinary deal" tile
    And I should not see the "Big hobbit feast - fireworks at midnight" tile

  # Regression test to ensure that related community content does not appear in the draft view.
  # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3262
  Scenario: The related content should not be shown in the draft view version as part of the content.
    When I am logged in as a facilitator of the "Middle earth daily" collection
    And I go to the homepage of the "Middle earth daily" collection
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "Middle earth nightly"
    And I press "Save as draft"
    And I click "View draft" in the "Entity actions" region
    Then I should see the text "Moderated"
    And I should see the text "Open collection"
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
