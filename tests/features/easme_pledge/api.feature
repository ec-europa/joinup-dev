@api
Feature: Basic tests related to the pledge.

  @terms
  Scenario: Create a pledge through the API.
    Given the following contact:
      | email       | test@example.com    |
      | name        | Pledge secretariat  |
      | Company webpage | http://www.test.com |
    And the following owner:
      | name                 | type                    |
      | Organisation example | Non-Profit Organisation |
    Given pledge content:
      | title                   | contact            | owner                |
      | Pledge about a solution | Pledge secretariat | Organisation example |
    Then I should have a "Pledge" content page titled "Pledge about a solution"
