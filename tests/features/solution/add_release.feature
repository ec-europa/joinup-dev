@api
Feature: "Add release" visibility options.
  In order to manage solutions
  As a moderator
  I need to be able to add "Release" rdf entities through UI.

  Scenario: "Add release" button should only be shown to moderators.
    Given the following solution:
      | name              | Release solution test             |
      | documentation     | text.pdf                          |

    When I am logged in as a "moderator"
    And I go to the homepage of the "Release solution test" solution
    Then I should see the link "Add release"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Release solution test" solution
    Then I should not see the link "Add release"

    When I am an anonymous user
    And I go to the homepage of the "Release solution test" solution
    Then I should not see the link "Add release"

  Scenario: Add release as a moderator.
    Given the following solution:
      | name              | Release solution test 2             |
      | uri               | https://release.solution/add/test/2 |
      | documentation     | text.pdf                            |
    And I am logged in as a moderator
    When I go to the homepage of the "Release solution test 2" solution
    And I click "Add release"
    Then I should see the heading "Add release"
    And the following fields should be present "Title, Version"
    When I fill in "Title" with "A release of the solution"
    When I fill in "Version" with "1.1"
    And I press "Save"
    Then the "A release of the solution" solution is a new release for "Release solution test 2"