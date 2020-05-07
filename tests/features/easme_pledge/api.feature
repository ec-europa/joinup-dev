@api
Feature: Basic tests related to the pledge.

  Scenario: Create a pledge through the API.
    Given pledge content:
      | title                   |
      | Pledge about a solution |
    Then I should have a "Pledge" content page titled "Pledge about a solution"
