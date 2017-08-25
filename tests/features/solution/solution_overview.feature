@api
Feature: Solutions Overview
  As a new visitor of the Joinup website
  To get an idea of the various solutions that are available
  I should see a list of all solutions

  Scenario: Check visibility of "Solutions" menu link.
    Given I am an anonymous user
    Then I should see the link "Solutions"
    When I click "Solutions"
    Then I should see the heading "Solutions"
    And I should see the text "A solution on Joinup is a framework, tool, or service either hosted directly on Joinup or federated from third-party repositories."
    # Check that all logged in users can see and access the link as well.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Solutions"
    When I click "Solutions"
    Then I should see the heading "Solutions"

  Scenario: Solution overview paging
    Given solutions:
      | title      | state     |
      | Arctic fox | validated |
      | Alpaca     | validated |
      | Boomalope  | validated |
      | Boomrat    | validated |
      | Megasloth  | validated |
      | Thrumbo    | validated |
      | Spelopede  | validated |
      | Muffalo    | validated |
      | Husky      | validated |
      | Gazelle    | validated |
      | Cow        | validated |
      | Panther    | validated |
      | Tortoise   | validated |
      | Warg       | validated |
    And I am an anonymous user
    And I am on the homepage
    When I click "Solutions"
    Then I should see the following tiles in the correct order:
      | Arctic fox |
      | Alpaca     |
      | Boomalope  |
      | Boomrat    |
      | Megasloth  |
      | Thrumbo    |
      | Spelopede  |
      | Muffalo    |
      | Husky      |
      | Gazelle    |
      | Cow        |
      | Panther    |
    And I should see the link "2"
    And I should see the link "Next ›"
    And I should see the link "Last »"
    But I should not see the link "« First"
    And I should not see the link "‹ Previous"
    When I click "Next ›"
    Then I should see the following tiles in the correct order:
      | Tortoise   |
      | Warg       |
    And I should see the link "1"
    And I should see the link "« First"
    And I should see the link "‹ Previous"
    But I should not see the link "Next ›"
    And I should not see the link "Last »"

  @terms
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
    Then I should see the link "Solutions"
    And I click "Solutions"

    # Access the page as a moderator to ensure proper caching.
    When I am logged in as a "moderator"
    And I am on the homepage
    And I click "Solutions"
    Then I should see the "Non electronic health" tile
    And I should see the "Closed data" tile
    And I should see the "Isolating Europe" tile
    And I should not see the "Uniting Europe" tile

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
    And I am on the homepage
    And I click "Solutions"
    Then I should see the "Non electronic health" tile
    And I should see the "Closed data" tile
    And I should see the "Isolating Europe" tile
    But I should not see the "Uniting Europe" tile

    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Solutions"
    When I click "Solutions"
    Then I should see the link "Non electronic health"
    And I should not see the text "Supports health-related fields"
    And I should see the link "Closed data"
    And I should not see the text "Facilitate access to data sets"
    And I should see the link "Isolating Europe"
    And I should not see the text "Reusable tools and services"
    When I click "Non electronic health"
    Then I should see the heading "Non electronic health"

    # Add new solution as a moderator to directly publish it.
    And I am logged in as a moderator
    When I go to the add solution form of the "Pikachu, I choose you" collection
    Then I should see the heading "Add Solution"
    When I fill in the following:
      | Title            | Colonies in Earth                                                      |
      | Description      | Some space mumbo jumbo description.                                    |
      | Spatial coverage | Belgium (http://publications.europa.eu/resource/authority/country/BEL) |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS          |
      | Name             | Ambrosio Morison                                                       |
      | E-mail address   | ambrosio.morison@example.com                                           |
    Then I select "http://data.europa.eu/dr8/TestScenario" from "Solution type"
    And I select "Demography" from "Policy domain"
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
    And I click "Solutions"
    Then I should see the text "Colonies in Earth"

    # Check the new solution as an anonymous user.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Solutions"
    When I click "Solutions"
    Then I should see the link "Colonies in Earth"

    # Clean up the solution that was created manually.
    And I delete the "Colonies in Earth" solution
