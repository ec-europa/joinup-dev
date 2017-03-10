@api
Feature: Solutions Overview

  Scenario: Check visibility of "Solutions" menu link.
    Given I am an anonymous user
    Then I should see the link "Solutions"
    When I click "Solutions"
    Then I should see the heading "Solutions"
    # Check that all logged in users can see and access the link as well.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Solutions"
    When I click "Solutions"
    Then I should see the heading "Solutions"

  # @todo The small header, which contains solutions link, should be removed for anonymous users on the homepage - https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2639.
  @terms
  Scenario: View solution overview as an anonymous user
    Given users:
      | name          | mail                              | roles |
      | Madam Shirley | i.dont.see.the.future@example.com |       |
    Given solutions:
      | title                 | description                    | state     |
      | Non electronic health | Supports health-related fields | validated |
      | Closed data           | Facilitate access to data sets | validated |
      | Isolating Europe      | Reusable tools and services    | validated |
    Given the following owner:
      | name              | type                    |
      | NonProfit example | Non-Profit Organisation |
    And the following contact:
      | email | foo@bar.com         |
      | name  | Information example |
    # Check that visiting as an anonymous does not create cache for all users.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Solutions"
    And I click "Solutions"

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
    And I am on the homepage
    And I click "Solutions"
    Then I should see the text "Non electronic health"
    And I should see the text "Closed data"
    And I should see the text "Isolating Europe"

    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Solutions"
    When I click "Solutions"
    Then I should see the link "Non electronic health"
    And I should see the text "Supports health-related fields"
    And I should see the link "Closed data"
    And I should see the text "Facilitate access to data sets"
    And I should see the link "Isolating Europe"
    And I should see the text "Reusable tools and services"
    When I click "Non electronic health"
    Then I should see the heading "Non electronic health"

    # Add new solution as a moderator to directly publish it.
    Given I am logged in as a moderator
    When I am on the homepage
    And I click "Propose solution"
    Then I should see the heading "Propose solution"
    When I fill in the following:
      | Title       | Colonies in Earth                   |
      | Description | Some space mumbo jumbo description. |
      | Documentation    | text.pdf                                                               |
      | Spatial coverage | Belgium (http://publications.europa.eu/resource/authority/country/BEL) |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS          |
    Then I select "http://data.europa.eu/eira/TestScenario" from "Solution type"
    And I select "Demography" from "Policy domain"
    And I attach the file "text.pdf" to "Documentation"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing contact information.
    And I press "Add existing" at the "Contact information" field
    And I fill in "Contact information" with "Information example"
    And I press "Add contact information"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "NonProfit example"
    And I press "Add owner"
    And I press "Publish"
    Then I visit the "Colonies in Earth" solution
    Then I should see the text "Colonies in Earth"

    And I am on the homepage
    And I click "Solutions"
    Then I should see the text "Colonies in Earth"

    # @todo: Normally the solution should go through a moderation process.
    # It will not be immediately available.
    # Check the new solution as an anonymous user.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Solutions"
    When I click "Solutions"
    Then I should see the link "Colonies in Earth"

    # Clean up the solution that was created manually.
    Then I delete the "Colonies in Earth" solution
