@api
Feature: Collections Overview

  Scenario: Check visibility of "Collections" menu link.
    # Check that all logged in users can see and access the link.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"

  Scenario: View collection overview as an authenticated user
    Given users:
      | name          | mail                         | roles |
      | Madam Shirley | i.see.the.future@example.com |       |
    Given collections:
      | title             | description                    |
      | eHealth           | Supports health-related fields |
      | Open Data         | Facilitate access to data sets |
      | Connecting Europe | Reusable tools and services    |
    Given organization:
      | name | Organization example |
    Then I commit the solr index

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
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
      | Title       | Colonies in space                   |
      | Description | Some space mumbo jumbo description. |
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing owner.
    And I press "Add existing Owner"
    And I fill in "Owner" with "Organization example"
    And I press "Add Owner"
    And I press "Save"
    Then I should see the text "Colonies in space"
    # Non UATable step.
    When I commit the solr index

    And I am on the homepage
    And I click "Collections"
    Then I should see the text "Colonies in space"

    # @todo: Normally the collection should go through a moderation process.
    # It will not be immediately available.
    # Check the new collection as an authenticated user.
    Given I am logged in as "Madam Shirley"
    When I am on the homepage
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the link "Colonies in space"

    # Clean up the collection that was created manually.
    Then I delete the "Colonies in space" collection
