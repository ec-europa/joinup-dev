@api
Feature: Asset release "delete" functionality.
  In order to manage releases
  As a solution facilitator
  I need to be able to delete "Release" rdf entities through UI.

  Background:
    Given the following owner:
      | name               |
      | Yet another owner |
    And the following contact:
      | name        | Yet another contact |
      | email       | yetanothercontact@example.com |
      | Website URL | http://example.com         |
    And the following solution:
      | title               | Yet another solution |
      | description         | Bored of finding new texts     |
      | documentation       | text.pdf                |
      | owner               | Yet another owner          |
      | contact information | Yet another contact        |
      | state               | validated               |
    And the following release:
      | title               | Yet another release |
      | description         | A sample release           |
      | documentation       | text.pdf                   |
      | release number      | 1                          |
      | release notes       | Changed release            |
      | is version of       | Yet another solution |
      | owner               | Yet another owner       |
      | contact information | Yet another contact          |
      | state               | validated                  |

  Scenario: "Delete" action should redirect users to the parent solution.
    # Navigating through UI should redirect the user to the solution homepage.
    When I am logged in as a "moderator"
    And I go to the "Yet another release" release edit form
    And I click "Delete"
    And I press "Delete"
    Then I should be on "/solution/yet-another-solution"
