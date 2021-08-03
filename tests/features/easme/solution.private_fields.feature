@api @terms
Feature: Solution editing.
  In order to manage solutions
  As a solution owner or solution facilitator
  I need to be able to edit solutions through UI.

  Scenario: Only the owner and the moderators can view the Private notes field.
    Given the following contact:
      | name  | Seward Shawn       |
      | email | seward@example.com |
    And owner:
      | name      | type    |
      | Acme inc. | Company |
    And users:
      | Username                    | E-mail                                  |
      | collection_owner            | collection.owner@example.com            |
      | irrelevant_collection_owner | irrelevant_collection.owner@example.com |
      | solution_owner              | solution.owner@example.com              |
    And collections:
      | title                 | state     |
      | Collection example    | validated |
      | Irrelevant collection | validated |
    And the following collection user memberships:
      | collection            | user                        | roles |
      | Collection example    | collection_owner            | owner |
      | Irrelevant collection | irrelevant_collection_owner | owner |
    And solution:
      | title               | Solution example     |
      | collection          | Collection example   |
      | description         | Just another one.    |
      | logo                | logo.png             |
      | banner              | banner.jpg           |
      | contact information | Seward Shawn         |
      | owner               | Acme inc.            |
      | private notes       | Blah Blah in private |
      | private file        | test.zip             |
      | state               | validated            |
    And the following solution user memberships:
      | solution         | user           | roles |
      | Solution example | solution_owner | owner |

    When I am logged in as a moderator
    And I go to the "Solution example" solution edit form
    Then I should see the text "Private notes"
    And the following fields should be present "Private notes, Private files"
    When I visit the "Solution example" solution
    And I click "About" in the "Left sidebar" region
    Then I should see the text "Blah Blah in private"
    And I should see the link "test.zip"

    When I am logged in as "irrelevant_collection_owner"
    And I visit the "Solution example" solution
    And I click "About" in the "Left sidebar" region
    Then I should not see the text "Blah Blah in private"
    And I should not see the link "test.zip"

    When I am logged in as "collection_owner"
    And I visit the "Solution example" solution
    And I click "About" in the "Left sidebar" region
    Then I should see the text "Blah Blah in private"
    And I should see the link "test.zip"

    When I am logged in as "solution_owner"
    And I go to the "Solution example" solution edit form
    Then I should see the text "Private notes"
    And the following fields should be present "Private notes, Private files"
    When I visit the "Solution example" solution
    And I click "About" in the "Left sidebar" region
    Then I should see the text "Blah Blah in private"
    And I should see the link "test.zip"

    # The collection facilitator is merely an authenticated user for a solution.
    When I am logged in as a facilitator of the "Collection example" collection
    And I go to the "Solution example" solution edit form
    Then I should not see the text "Private notes"
    And the following fields should not be present "Private notes, Private files"
    When I visit the "Solution example" solution
    And I click "About" in the "Left sidebar" region
    Then I should not see the text "Blah Blah in private"
    And I should not see the link "test.zip"

    When I am logged in as a facilitator of the "Solution example" solution
    And I go to the "Solution example" solution edit form
    Then I should not see the text "Private notes"
    And the following fields should not be present "Private notes, Private files"
    When I visit the "Solution example" solution
    And I click "About" in the "Left sidebar" region
    Then I should not see the text "Blah Blah in private"
    And I should not see the link "test.zip"

    When I am logged in as a user with the authenticated role
    And I go to the "Solution example" solution edit form
    Then I should not see the text "Private notes"
    And the following fields should not be present "Private notes, Private files"
    When I visit the "Solution example" solution
    And I click "About" in the "Left sidebar" region
    Then I should not see the text "Blah Blah in private"
    And I should not see the link "test.zip"

    When I am not logged in
    And I go to the "Solution example" solution edit form
    Then I should not see the text "Private notes"
    And the following fields should not be present "Private notes, Private files"
    When I visit the "Solution example" solution
    And I click "About" in the "Left sidebar" region
    Then I should not see the text "Blah Blah in private"
    And I should not see the link "test.zip"

