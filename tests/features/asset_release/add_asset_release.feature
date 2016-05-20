@api
Feature: "Add release" visibility options.
  In order to manage asset releases
  As a moderator
  I need to be able to add "Release" rdf entities through UI.

  Scenario: "Add release" button should only be shown to moderators.
    Given the following solution:
      | title         | Release solution test |
      | description   | My awesome solution   |
      | documentation | text.pdf              |

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
    Given the following solutions:
      | title          | description        | documentation |
      | Release Test 1 | test description 1 | text.pdf      |
      | Release Test 2 | test description 2 | text.pdf      |
    # Check that the release cannot take the title of another solution.
    And I am logged in as a moderator
    When I go to the homepage of the "Release Test 1" solution
    And I click "Add release"
    Then I should see the heading "Add Asset release"
    And the following fields should be present "Name, Release number, Release notes"
    When I fill in "Name" with "Release Test 2"
    And I fill in "Release number" with "1.1"
    And I fill in "Release notes" with "Changed release."
    And I press "Save"
    Then I should see the error message "Content with name Release Test 2 already exists."

    # Check that the same title as the parent is valid.
    When I fill in "Name" with "Release Test 1 v2"
    And I press "Save"
    Then I should have 1 release
    And I should see the text "Is version of"
    And I should see the text "Release Test 1"

    # Verify that the "Release Test 1 v2" is registered as a release to "Release Test 1" solution.
    When I go to the homepage of the "Release Test 1" solution
    Then I should see the text "Releases"
    And I should see the text "Release Test 1 v2"

    # Check that the release cannot take the title of another release in another solution.
    And I am logged in as a moderator
    When I go to the homepage of the "Release Test 2" solution
    And I click "Add release"
    Then I should see the heading "Add Asset release"
    And the following fields should be present "Name, Release number, Release notes"
    When I fill in "Name" with "Release Test 1 v2"
    And I fill in "Release number" with "1.1"
    And I fill in "Release notes" with "Changed release."
    And I press "Save"
    Then I should see the error message "Content with name Release Test 1 v2 already exists."

    # Check relationship with solution.
    When I go to the homepage of the "Release Test 1" solution
    And I should see the text "Releases"
    And I should see the text "Release Test 1 v2"
    When I click "Release Test 1 v2"
    Then I should see the text "Is version of"
    And I should see the text "Release Test 1"

    # Cleanup created release.
    Then I delete the "Release Test 1 v2" asset release