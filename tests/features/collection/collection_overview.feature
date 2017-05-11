@api
Feature: Collections Overview

  Scenario: Check visibility of "Collections" menu link.
    Given I am an anonymous user
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"
    # Check that all logged in users can see and access the link as well.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"

  # @todo The small header, which contains collections link, should be removed for anonymous users on the homepage - https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2639.
  @terms
  Scenario: View collection overview as an anonymous user
    Given users:
      | Username      | E-mail                       |
      | Madam Shirley | i.see.the.future@example.com |
    Given collections:
      | title             | description                    | state     |
      | E-health          | Supports health-related fields | validated |
      | Open Data         | Facilitate access to data sets | validated |
      | Connecting Europe | Reusable tools and services    | validated |
    Given the following owner:
      | name                 | type                    |
      | Organisation example | Non-Profit Organisation |
    # Check that visiting as an anonymous does not create cache for all users.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Collections"
    And I click "Collections"

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
    And I am on the homepage
    And I click "Collections"
    Then I should see the text "E-health"
    And I should see the text "Open Data"
    And I should see the text "Connecting Europe"

    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the link "E-health"
    And I should see the text "Supports health-related fields"
    And I should see the link "Open Data"
    And I should see the text "Facilitate access to data sets"
    And I should see the link "Connecting Europe"
    And I should see the text "Reusable tools and services"
    When I click "E-health"
    Then I should see the heading "E-health"

    # Add new collection as a moderator to directly publish it.
    Given I am logged in as a moderator
    When I am on the homepage
    And I click "Propose collection"
    Then I should see the heading "Propose collection"
    When I fill in the following:
      | Title       | Colonies in space                   |
      | Description | Some space mumbo jumbo description. |
    When I select "Employment and Support Allowance" from "Policy domain"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    And I press "Publish"
    Then I visit the "Colonies in space" collection
    Then I should see the text "Colonies in space"

    And I am on the homepage
    And I click "Collections"
    Then I should see the text "Colonies in space"

    # @todo: Normally the collection should go through a moderation process.
    # It will not be immediately available.
    # Check the new collection as an anonymous user.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the link "Colonies in space"

    # Clean up the collection that was created manually.
    Then I delete the "Colonies in space" collection

  @terms
  Scenario: View collection detailed information in the About page
    Given the following owner:
      | name         | type                |
      | Tamsin Irwin | Industry consortium |
    And the following contact:
      | email       | irwinbvba@example.com        |
      | name        | Irwin BVBA made-up company   |
      | Website URL | http://www.example.org/irwin |
    And the following collection:
      | title               | Fitness at work                                                      |
      | description         | This collection is intended to show ways of being fit while working. |
      | policy domain       | E-health                                                             |
      | owner               | Tamsin Irwin                                                         |
      | abstract            | Fit while working is dope.                                           |
      | logo                | logo.png                                                             |
      | banner              | banner.jpg                                                           |
      | contact information | Irwin BVBA made-up company                                           |
      | spatial coverage    | Belgium                                                              |
      | closed              | no                                                                   |
      | elibrary creation   | facilitators                                                         |
      | moderation          | no                                                                   |
      | state               | validated                                                            |

    When I go to the homepage of the "Fitness at work" collection
    And I click "About" in the "Left sidebar" region
    Then I should see the heading "About Fitness at work"
    And I should see the text "Fit while working is dope."
    And I should see the text "This collection is intended to show ways of being fit while working."
    And I should see the text "Tamsin Irwin"
    And I should see the text "Irwin BVBA made-up company"
    And I should see the text "E-health"
    And I should see the text "Belgium"
