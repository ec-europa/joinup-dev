@api
Feature: Creation of owners through UI
  In order to manage owners
  As a user
  I need to be able to create owners through the UI.

  Scenario: Create a owner of type person with an unique name
    Given the following person:
      | name | Foo |
    And the following organisation:
      | name | Bar |
    When I am logged in as an "authenticated user"
    And I visit "rdf_entity/add/person"
    Then I should see the heading "Add Person"
    # Verify uniqueness of name amongst owners.
    When I fill in "Name" with "Foo"
    And I press "Save"
    Then I should see the error message "Content with name Foo already exists. Please choose a different name."
    When I fill in "Name" with "Bar"
    And I press "Save"
    Then I should see the error message "Content with name Bar already exists. Please choose a different name."
    # Verify creation.
    When I fill in "Name" with "Baz"
    And I press "Save"
    Then I should see the heading "Baz"
    # Cleanup created owner.
    Then I delete the "Baz" person

  Scenario: Create a owner of type organisation with an unique name
    Given the following person:
      | name | Foo |
    And the following organisation:
      | name | Bar |
    When I am logged in as an "authenticated user"
    And I visit "rdf_entity/add/organisation"
    Then I should see the heading "Add Organisation"
    # Verify uniqueness of name amongst owners.
    When I fill in "Name" with "Foo"
    And I press "Save"
    Then I should see the error message "Content with name Foo already exists. Please choose a different name."
    When I fill in "Name" with "Bar"
    And I press "Save"
    Then I should see the error message "Content with name Bar already exists. Please choose a different name."
    # Verify creation.
    When I fill in "Name" with "Baz"
    And I press "Save"
    Then I should see the heading "Baz"
    # Cleanup created owner.
    Then I delete the "Baz" organisation