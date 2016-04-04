@api
Feature: "Add solution" visibility options.
  In order to manage solutions
  As a moderator
  I need to be able to add "Solution" rdf entities through UI.

  Scenario: "Add solution" button should only be shown to moderators.
    Given the following collection:
      | name   | Collection solution test             |
      | uri    | https://collection.solution/add/test |
      | logo   | logo.png                             |

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
      | name   | Collection solution test 2              |
      | uri    | https://collection.solution/add/test/2  |
      | logo   | logo.png                                |
    And I am logged in as a moderator

    When I go to the homepage of the "Collection solution test 2" collection
    And I click "Add solution"
    Then I should see the heading "Add Interoperability Solution"
    And the following fields should be present "Title, Description, Documentation"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in the following:
      | Title             | Collection solution add solution       |
      | Description       | This is a test text                    |
      | Documentation     | text.pdf                               |
      | eLibrary creation | 1                                      |
    And I press "Save"
    And the "Collection solution test 2" collection has a solution named "Collection solution add solution"
