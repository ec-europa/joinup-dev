@api
Feature: Collection API
  In order to manage collections programmatically
  As a backend developer
  I need to be able to use the Collection API

  Scenario: Programmatically create a collection
    Given the following collection:
      | title             | Open Data Initiative |
      | logo              | logo.png             |
      | banner            | banner.jpg           |
      | moderation        | no                   |
      | closed            | no                   |
      | elibrary creation | facilitators         |
    Then I should have 1 collection

  Scenario: Programmatically create a collection using only the name
    Given the following collection:
      | title | EU Interoperability Support Group |
    Then I should have 1 collection

  Scenario: Assign OG ownership during creation of collections through UI
    Given the following person:
      | name | Person collection API example |
    And users:
      | name                | pass |
      | Collection API user | pass |
    And I am logged in as "Collection API user"
    When I am on the homepage
    And I click "Propose collection"
    Then I should see the heading "Propose collection"
    When I fill in the following:
      | Title       | Collection API example                       |
      | Description | We do not care that much about descriptions. |
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I press "Add existing owner" at the "Owner" field
    And I fill in "Owner" with "Person collection API example"
    And I press "Add owner"
    And I check "Closed collection"
    And I select "Only members can publish new content" from "eLibrary creation"
    And I check "Moderated"
    And I press "Save"
    Then I should see the heading "Collection API example"
    And the user "Collection API user" should be the owner of the "Collection API example" collection
