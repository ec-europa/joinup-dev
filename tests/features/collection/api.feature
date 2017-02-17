@api @terms
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
      | policy domain     | E-health             |
      | state             | validated            |
    Then I should have 1 collection

  Scenario: Programmatically create a collection using only the name
    Given the following collection:
      | title | EU Interoperability Support Group |
      | state | validated                         |
    Then I should have 1 collection

  Scenario: Assign ownership when a collection is created through UI.
    Given the following owner:
      | name     | type                  |
      | Prayfish | Private Individual(s) |
    And I am logged in as an "authenticated user"
    When I am on the homepage
    And I click "Propose collection"
    Then I should see the heading "Propose collection"
    When I fill in the following:
      | Title         | Collection API example                       |
      | Description   | We do not care that much about descriptions. |
    When I select "Data gathering, data processing" from "Policy domain"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Prayfish"
    And I press "Add owner"
    And I check "Closed collection"
    And I check "Moderated"
    And I press "Propose"
    Then I should see the heading "Collection API example"
    And I should own the "Collection API example" collection
    # Cleanup step.
    Then I delete the "Collection API example" collection
