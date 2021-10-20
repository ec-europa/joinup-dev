@api @group-d
Feature: Validation of owners created through UI
  In order to ensure that my data is complete
  As a collection owner
  I need to prevent incomplete owners being created

  @terms
  Scenario: Owner fields are required
    Given I am logged in as a user with the "authenticated" role
    And I go to the propose collection form
    When I press "Add new" at the "Owner" field
    And I press "Create owner"
    Then I should see the error message "Name field is required."
    And I fill in "Name" with "Leandro Keen"
    And I press "Create owner"
    Then I should see "Leandro Keen"
    And I should not see the error message "Name field is required."
