@api
Feature: Asset distribution editing.
  In order to manage asset distributions
  As a solution owner or solution facilitator
  I need to be able to edit asset distributions through UI.

  Background:
    Given the following solutions:
      | title      | description        | state     |
      | Solution A | Sample description | validated |
      | Solution B | Sample description | validated |

    And the following collection:
      | title      | Collection example     |
      | affiliates | Solution A, Solution B |
      | state      | validated              |
    And the following distribution:
      | title       | Asset distribution example |
      | description | Sample description         |
      | access url  | test.zip                   |
      | solution    | Solution A                 |
    And the following release:
      | title         | Asset release example      |
      | description   | Release description        |
      | is version of | Solution A                 |
      | distribution  | Asset distribution example |

  Scenario: "Edit" button should be shown to facilitators of the related solution.
    When I am logged in as a facilitator of the "Solution A" solution
    And I go to the homepage of the "Asset distribution example" asset distribution
    Then I should see the link "Edit" in the "Entity actions" region

    When I am logged in as a facilitator of the "Solution B" solution
    And I go to the homepage of the "Asset distribution example" asset distribution
    Then I should not see the link "Edit" in the "Entity actions" region

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Asset distribution example" asset distribution
    Then I should not see the link "Edit" in the "Entity actions" region

    When I am an anonymous user
    And I go to the homepage of the "Asset distribution example" asset distribution
    Then I should not see the link "Edit" in the "Entity actions" region

  Scenario: Edit a distribution as a solution facilitator.
    When I am logged in as a facilitator of the "Solution A" solution
    And I go to the homepage of the "Asset distribution example" asset distribution
    And I click "Edit"
    Then I should see the heading "Edit Distribution Asset distribution example"
    When I fill in "Title" with "Asset distribution example revised"
    And I press "Save"
    Then I should see the heading "Asset distribution example revised"
