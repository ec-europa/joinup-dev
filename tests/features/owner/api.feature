@api @group-f
Feature: Owner API
  In order to manage owners programmatically
  As a backend developer
  I need to be able to use the Owner API

  Scenario: Create a person
    Given owner:
      | name             | type                  |
      | Owner API person | Private Individual(s) |
    Then I should have 1 owner

  Scenario: Create an organisation
    Given owner:
      | name                   | type                             |
      | Owner API organisation | Academia/Scientific organisation |
    Then I should have 1 owner
