@api
Feature: "Add solution" visibility options.
  In order to manage solutions
  As a moderator
  I need to be able to add "Solution" rdf entities through UI.

  Scenario: "Add solution" button should only be shown to moderators.
    Given the following collection:
      | title | Collection solution test |
      | logo  | logo.png                 |

    When I am logged in as a "moderator"
    And I go to the homepage of the "Collection solution test" collection
    Then I should see the link "Add solution"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Collection solution test" collection
    Then I should not see the link "Add solution"

    When I am an anonymous user
    And I go to the homepage of the "Collection solution test" collection
    Then I should not see the link "Add solution"

  Scenario: Add solution as a moderator.
    Given the following collection:
      | title | Collection solution test 2 |
      | logo  | logo.png                   |
    And I am logged in as a moderator

    When I go to the homepage of the "Collection solution test 2" collection
    And I click "Add solution"
    Then I should see the heading "Add Interoperability Solution"
    And the following fields should be present "Title, Description, Documentation"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in the following:
      | Title             | Collection solution add solution |
      | Description       | This is a test text              |
      | Documentation     | text.pdf                         |
    And I press "Save"
    # The name of the solution should exist in the block of the relative content in a collection.
    When I go to the homepage of the "Collection solution test 2" collection
    Then I should see the link "Collection solution add solution"
    When I click "Collection solution add solution"
    Then I should see the heading "Collection solution add solution"
    # Clean up the solution that was created through the UI.
    Then I delete the "Collection solution add solution" solution
