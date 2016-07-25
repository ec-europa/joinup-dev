@api
Feature: Creation of owners through UI
  In order to manage owners
  As a user
  I need to be able to create owners through the UI.

  Scenario: Create a owner of type person.
    When I am logged in as an "authenticated user"
    And I visit "rdf_entity/add/person"
    Then I should see the heading "Add Person"
    # Verify creation.
    When I fill in "Name" with "Baz"
    And I press "Save"
    Then I should see the heading "Baz"
    # Cleanup created owner.
    Then I delete the "Baz" person

  Scenario: Create a owner of type organisation with an unique name
    When I am logged in as an "authenticated user"
    And I visit "rdf_entity/add/organisation"
    Then I should see the heading "Add Organisation"
    When I fill in "Name" with "Baz"
    And I press "Save"
    Then I should see the heading "Baz"
    # Cleanup created owner.
    Then I delete the "Baz" organisation