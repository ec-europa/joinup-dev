@api
Feature: Solutions Overview
  As a new visitor of the Joinup website
  To get an idea of the various solutions that are available
  I should see a list of all solutions

  Scenario: Solution overview paging
    Given solutions:
      | title      | creation date     | state     |
      | Arctic fox | 2018-10-04 8:21am | validated |
      | Alpaca     | 2018-10-04 8:31am | validated |
      | Boomalope  | 2018-10-04 8:28am | validated |
      | Boomrat    | 2018-10-04 8:35am | validated |
      | Megasloth  | 2018-10-04 8:01am | validated |
      | Thrumbo    | 2018-10-04 8:07am | validated |
      | Spelopede  | 2018-10-04 8:18am | validated |
      | Muffalo    | 2018-10-04 8:59am | validated |
      | Husky      | 2018-10-04 8:00am | validated |
      | Gazelle    | 2018-10-04 8:43am | validated |
      | Cow        | 2018-10-04 8:27am | validated |
      | Panther    | 2018-10-04 8:22am | validated |
      | Tortoise   | 2018-10-04 8:34am | validated |
      | Warg       | 2018-10-04 8:39am | validated |
    And I am an anonymous user
    And I am on the homepage
    When I click "More solutions"
    Then I should see the following tiles in the correct order:
      | Muffalo    |
      | Gazelle    |
      | Warg       |
      | Boomrat    |
      | Tortoise   |
      | Alpaca     |
      | Boomalope  |
      | Cow        |
      | Panther    |
      | Arctic fox |
      | Spelopede  |
      | Thrumbo    |
    And I should see the link "2"
    # Next and last page links are rendered as icons "›" and "»", but there is an
    # help text that is meant for screen readers and also visualised on mouseover.
    And I should see the link "Next page"
    And I should see the link "Last page"
    But I should not see the link "First page"
    And I should not see the link "Go to previous page"
    When I click "Next page"
    Then I should see the following tiles in the correct order:
      | Megasloth |
      | Husky     |
    And I should see the link "1"
    And I should see the link "First page"
    And I should see the link "Go to previous page"
    But I should not see the link "Next page"
    And I should not see the link "Last page"

  @terms @uploadFiles:logo.png,banner.jpg
  Scenario: View solution overview as an anonymous user
    Given users:
      | Username      | E-mail                            |
      | Madam Shirley | i.dont.see.the.future@example.com |
    And the following collection:
      | title | Pikachu, I choose you |
      | logo  | logo.png              |
      | state | validated             |
    And solutions:
    # As of ISAICP-3618 descriptions should not be visible in regular tiles.
      | title                 | description                    | state     |
      | Non electronic health | Supports health-related fields | validated |
      | Closed data           | Facilitate access to data sets | validated |
      | Isolating Europe      | Reusable tools and services    | validated |
      | Uniting Europe        | Unusable tools and services    | draft     |
    And the following owner:
      | name              | type                    |
      | NonProfit example | Non-Profit Organisation |
    # Check that visiting as an anonymous does not create cache for all users.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "More solutions"
    When I click "More solutions"
    Then I should see the heading "Solutions"
    And I should see the text "A solution on Joinup is a framework, tool, or service either hosted directly on Joinup or federated from third-party repositories."
    And the page should be cacheable

    # Access the page as a moderator to ensure proper caching.
    When I am logged in as a "moderator"
    And I am on the homepage
    Then I should see the link "More solutions"
    When I click "More solutions"
    Then I should see the heading "Solutions"
    And I should see the text "A solution on Joinup is a framework, tool, or service either hosted directly on Joinup or federated from third-party repositories."
    And I should see the "Non electronic health" tile
    And I should see the "Closed data" tile
    And I should see the "Isolating Europe" tile
    And I should not see the "Uniting Europe" tile
    And the page should be cacheable

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
    And I am on the homepage
    Then I should see the link "More solutions"
    When I click "More solutions"
    Then I should see the heading "Solutions"
    And I should see the text "A solution on Joinup is a framework, tool, or service either hosted directly on Joinup or federated from third-party repositories."
    Then I should see the "Non electronic health" tile
    And I should see the "Closed data" tile
    And I should see the "Isolating Europe" tile
    But I should not see the "Uniting Europe" tile
    And the page should be cacheable

    # Once more for anonymous.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "More solutions"
    When I click "More solutions"
    Then I should see the heading "Solutions"
    And I should see the text "A solution on Joinup is a framework, tool, or service either hosted directly on Joinup or federated from third-party repositories."
    And I should see the link "Non electronic health"
    And I should not see the text "Supports health-related fields"
    And I should see the link "Closed data"
    And I should not see the text "Facilitate access to data sets"
    And I should see the link "Isolating Europe"
    And I should not see the text "Reusable tools and services"
    And the page should be cacheable

    When I click "Non electronic health"
    Then I should see the heading "Non electronic health"

    # Add new solution as a moderator to directly publish it.
    And I am logged in as a moderator
    When I go to the add solution form of the "Pikachu, I choose you" collection
    Then I should see the heading "Add Solution"
    When I fill in the following:
      | Title                 | Colonies in Earth                                                      |
      | Description           | Some space mumbo jumbo description.                                    |
      | Geographical coverage | Belgium (http://publications.europa.eu/resource/authority/country/BEL) |
      | Language              | http://publications.europa.eu/resource/authority/language/VLS          |
      | Name                  | Ambrosio Morison                                                       |
      | E-mail address        | ambrosio.morison@example.com                                           |
    Then I select "http://data.europa.eu/dr8/DataExchangeService" from "Solution type"
    And I select "Demography" from "Topic"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I upload the file "text.pdf" to "Upload a new file or enter a URL"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "NonProfit example"
    And I press "Add owner"
    And I press "Publish"
    And I visit the "Colonies in Earth" solution
    And I should see the text "Colonies in Earth"

    When I am on the homepage
    And I click "More solutions"
    Then I should see the text "Colonies in Earth"
    And the page should be cacheable

    # Check the new solution as an anonymous user.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "More solutions"
    When I click "More solutions"
    Then I should see the link "Colonies in Earth"
    And the page should be cacheable

    # Clean up the solution that was created manually.
    And I delete the "Colonies in Earth" solution

  Scenario: Users are able to filter solutions they have created or that are featured site-wide.
    Given users:
      | Username        | E-mail                      |
      | Marjorie Parker | marjorie.parker@example.com |
      | Ryker Brandon   | ryker.brandon@example.com   |
      | Joann Womack    | joann.womack@example.com    |
    And the following collections:
      | title                 | state     |
      | Insane Wooden Crystal | validated |
    And the following solutions:
      | title                        | collection            | state     | featured | author          |
      | Subdivision Morbid           | Insane Wooden Crystal | validated | yes      | Marjorie Parker |
      | Long Tungsten                | Insane Wooden Crystal | validated | no       | Ryker Brandon   |
      | Hungry Disappointed Tungsten | Insane Wooden Crystal | validated | yes      | Marjorie Parker |
      | Lost Yard                    | Insane Wooden Crystal | validated | no       | Joann Womack    |
      | Lost Scattered Fish          | Insane Wooden Crystal | validated | no       | Joann Womack    |
      | Silver Gravel                | Insane Wooden Crystal | validated | no       | Joann Womack    |
    # Technical: use a separate step to create a collection associated to the anonymous user.
    And the following solution:
      | title      | Flag Rough            |
      | collection | Insane Wooden Crystal |
      | state      | validated             |
      | featured   | no                    |

    When I am logged in as "Joann Womack"
    And I click "Solutions"
    Then the "My solutions content" inline facet should allow selecting the following values:
      | My solutions (3)       |
      | Featured solutions (2) |
    And the page should be cacheable
    When I click "My solutions" in the "My solutions content" inline facet
    Then I should see the following tiles in the correct order:
      | Lost Yard           |
      | Lost Scattered Fish |
      | Silver Gravel       |
    And the "My solutions content" inline facet should allow selecting the following values:
      | Featured solutions (2) |
      | All solutions          |
    And the page should be cacheable
    # Regression test to ensure that the facets are cached by user.
    # Subsequent page loads of the collections page would lead to cached facets
    # to be leaked to other users.
    # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3777
    When I click "All solutions" in the "My solutions content" inline facet
    Then the "My solutions content" inline facet should allow selecting the following values:
      | My solutions (3)       |
      | Featured solutions (2) |
    And the page should be cacheable

    When I am logged in as "Ryker Brandon"
    When I click "Solutions"
    Then the "My solutions content" inline facet should allow selecting the following values:
      | Featured solutions (2) |
      | My solutions (1)       |
    And the page should be cacheable
    When I click "My solutions" in the "My solutions content" inline facet
    Then I should see the following tiles in the correct order:
      | Long Tungsten |
    And the "My solutions content" inline facet should allow selecting the following values:
      | Featured solutions (2) |
      | All solutions          |
    And the page should be cacheable
    # Verify that the facets are cached for the correct user by visiting again
    # the collections page without any facet filter.
    When I click "All solutions" in the "My solutions content" inline facet
    Then the "My solutions content" inline facet should allow selecting the following values:
      | Featured solutions (2) |
      | My solutions (1)       |
    And the page should be cacheable

    When I am an anonymous user
    And I click "Solutions"
    # The anonymous user has no access to the "My solutions" facet entry.
    Then the "My solutions content" inline facet should allow selecting the following values:
      | Featured solutions (2) |
    And the page should be cacheable
    When I click "Featured solutions" in the "My solutions content" inline facet
    Then I should see the following tiles in the correct order:
      | Subdivision Morbid           |
      | Hungry Disappointed Tungsten |
    And the "My solutions content" inline facet should allow selecting the following values:
      | All solutions |
    And the page should be cacheable
    When I click "All solutions" in the "My solutions content" inline facet
    Then the "My solutions content" inline facet should allow selecting the following values:
      | Featured solutions (2) |
    And the page should be cacheable

    When I am logged in as "Ryker Brandon"
    And I click "Solutions"
    And I click "Featured solutions" in the "My solutions content" inline facet
    Then I should see the following tiles in the correct order:
      | Subdivision Morbid           |
      | Hungry Disappointed Tungsten |
    And the page should be cacheable

  Scenario: Solution overview active trail should persist on urls with arguments.
    Given I am an anonymous user
    And I visit "/solutions?a=1"
    Then "Solutions" should be the active item in the "Header menu" menu
