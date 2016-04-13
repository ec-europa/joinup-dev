@api
Feature: "Add asset distribution" visibility options.
  In order to manage distributions
  As a moderator
  I need to be able to add "Asset distribution" rdf entities through UI.

  Scenario: "Add distribution" button should only be shown to moderators.
    Given the following collection:
      | name | Asset Distribution Test |
      | logo | logo.png                |
    And the following solution:
      | name        | Asset random name                |
      | description | Some reusable random description |
      | collection  | Asset Distribution Test          |

    When I am logged in as a "moderator"
    And I go to the homepage of the "Asset random name" solution
    Then I should see the link "Add distribution"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Asset random name" solution
    Then I should not see the link "Add distribution"

    When I am an anonymous user
    And I go to the homepage of the "Asset random name" solution
    Then I should not see the link "Add distribution"

  Scenario: Add distribution as a moderator.
    Given the following collection:
      | name | Asset Distribution Test2 |
      | logo | logo.png                 |
    And the following solution:
      | name        | Asset another random name        |
      | description | Some reusable random description |
      | collection  | Asset Distribution Test2         |
    And I am logged in as a moderator
    When I go to the homepage of the "Asset another random name" solution
    And I click "Add distribution"
    Then I should see the heading "Add Asset distribution"
    When I fill in "Title" with "Custom title of asset distribution"
    And I attach the file "test.zip" to "Add a new file"
    And I press "Save"
    Then I should have 1 asset distribution
    When I go to the homepage of the "Asset another random name" solution
    Then I should see the text "Distribution"
    And I should see the link "Custom title of asset distribution"
    And the "Custom title of asset distribution" asset distribution is related to the "Asset another random name" solution
