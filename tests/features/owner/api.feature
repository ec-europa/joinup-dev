Feature: Owner API
  In order to manage owners programmatically
  As a backend developer
  I need to be able to use the Owner API

  Scenario: Programmatically create a person
    Given the following person:
      | name             | Owner API person |
    Then I should have 1 person

  Scenario: Programmatically create a organization
    Given the following organization:
      | name             | Owner API organization |
    Then I should have 1 organization
