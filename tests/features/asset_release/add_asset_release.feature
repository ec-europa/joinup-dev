@api
Feature: "Add release" visibility options.
  In order to manage releases
  As a solution facilitator
  I need to be able to add "Release" rdf entities through UI.

  Scenario: "Add release" button should only be shown to solution facilitators.
    Given the following solution:
      | title         | Release solution test |
      | description   | My awesome solution   |
      | documentation | text.pdf              |
      | state         | validated             |

    When I am logged in as a "facilitator" of the "Release solution test" solution
    And I go to the homepage of the "Release solution test" solution
    # The user has to press the '+' button for the option "Add release" to be
    # visible.
    Then I should see the link "Add release"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Release solution test" solution
    Then I should not see the link "Add release"

    When I am an anonymous user
    And I go to the homepage of the "Release solution test" solution
    Then I should not see the link "Add release"

  Scenario: Add release as a solution facilitator.
    Given the following owner:
      | name                 | type    |
      | Organisation example | Company |
    And the following solutions:
      | title          | description        | documentation | owner                | state     |
      | Release Test 1 | test description 1 | text.pdf      | Organisation example | validated |
      | Release Test 2 | test description 2 | text.pdf      | Organisation example | validated |
    # Check that the release cannot take the title of another solution.
    When I am logged in as a "facilitator" of the "Release Test 1" solution
    When I go to the homepage of the "Release Test 1" solution
    And I click "Add release"
    Then I should see the heading "Add Release"
    And the following fields should be present "Name, Release number, Release notes, Documentation, Spatial coverage, Keyword, Status, Contact information, Language"
    # The entity is new, so the current workflow state should not be shown.
    And the following fields should not be present "Current workflow state"
    When I fill in "Name" with "Release Test 2"
    And I fill in "Release number" with "1.1"
    And I fill in "Release notes" with "Changed release."
    And I press "Publish"
    Then I should see the error message "Content with name Release Test 2 already exists."

    # Check that the same title as the parent is valid.
    When I fill in "Name" with "Release Test 1 v2"
    And I press "Publish"
    Then I should have 1 release

    # Verify that the "Release Test 1 v2" is registered as a release to "Release Test 1" solution.
    When I go to the homepage of the "Release Test 1" solution
    Then I should see the text "Download releases"
    When I click "Download releases"
    Then I should see the text "Release Test 1 v2"

    # Check that the release cannot take the title of another release in another solution.
    When I am logged in as a "facilitator" of the "Release Test 2" solution
    When I go to the homepage of the "Release Test 2" solution
    And I click "Add release"
    Then I should see the heading "Add Release"
    And the following fields should be present "Name, Release number, Release notes"
    When I fill in "Name" with "Release Test 1 v2"
    And I fill in "Release number" with "1.1"
    And I fill in "Release notes" with "Changed release."
    And I press "Publish"
    Then I should see the error message "Content with name Release Test 1 v2 already exists."

    # Cleanup created release.
    Then I delete the "Release Test 1 v2" release
