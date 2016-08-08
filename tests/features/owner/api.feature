Feature: Owner API
  In order to manage owners programmatically
  As a backend developer
  I need to be able to use the Owner API

  Scenario: Programmatically create a person
    Given the following person:
      | name             | Owner API person |
    Then I should have 1 person

  Scenario: Programmatically create a organisation
    Given the following organisation:
      | name             | Owner API organisation |
    Then I should have 1 organisation
