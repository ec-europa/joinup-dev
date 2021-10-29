@api @group-g
Feature: Contact information API
  In order to manage contact information programmatically
  As a backend developer
  I need to be able to use the Contact information API

  Scenario: Programmatically create a contact information
    Given the following contact:
      | email       | foo@bar.com                 |
      | name        | Contact information API foo |
      | Website URL | http://www.example.org      |
    Then I should have 1 contact information

  Scenario: Programmatically create a contact information using only the mandatory fields
    Given the following contact:
      | email | baz@qux.com                 |
      | name  | Contact information API bar |
    Then I should have 1 contact information
