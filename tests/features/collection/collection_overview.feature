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
  Scenario: View collection overview as an anonymous user
    Given users:
      | name          | mail                         | roles |
      | Madam Shirley | i.see.the.future@example.com |       |
    Given collections:
      | title             | description                    |
      | eHealth           | Supports health-related fields |
      | Open Data         | Facilitate access to data sets |
      | Connecting Europe | Reusable tools and services    |
    Given organisation:
      | name | Organisation example |
    Then I commit the solr index
    # Check that visiting as an anonymous does not create cache for all users.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Collections"
    And I click "Collections"

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
    And I am on the homepage
    And I click "Collections"
    Then I should see the text "eHealth"
    And I should see the text "Open Data"
    And I should see the text "Connecting Europe"

    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the link "eHealth"
    And I should see the text "Supports health-related fields"
    And I should see the link "Open Data"
    And I should see the text "Facilitate access to data sets"
    And I should see the link "Connecting Europe"
    And I should see the text "Reusable tools and services"
    When I click "eHealth"
    Then I should see the heading "eHealth"

    # Add new collection.
    Given I am logged in as "Madam Shirley"
    When I am on the homepage
    And I click "Propose collection"
    Then I should see the heading "Propose collection"
    When I fill in the following:
      | Title         | Colonies in space                                                       |
      | Description   | Some space mumbo jumbo description.                                     |
      | Policy domain | Internal Market (WIP!) (http://joinup.eu/policy-domain/internal-market) |
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing owner.
    And I press "Add existing owner"
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    And I press "Save"
    Then I visit the "Colonies in space" collection
    Then I should see the text "Colonies in space"
    # Non UATable step.
    When I commit the solr index

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
