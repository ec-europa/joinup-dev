@api
Feature: Solution editing.
  In order to manage solutions
  As a solution owner or solution facilitator
  I need to be able to edit solutions through UI.

  Background:
    Given the following contact:
      | name  | Seward Shawn       |
      | email | seward@example.com |
    And organisation:
      | name | Acme inc. |
    And users:
      | name         | mail                     |
      | Yancy Burton | yancy.burton@example.com |
    And collection:
      | title | Collection example |
    # Assign facilitator role in order to allow creation of a solution.
    # In UAT this can be done by creating the collection through the UI
    # with the related user.
    And the following collection user memberships:
      | collection         | user         | roles       |
      | Collection example | Yancy Burton | facilitator |
    And solution:
      | title               | Another solution  |
      | description         | Just another one. |
      | logo                | logo.png          |
      | banner              | banner.jpg        |
      | contact information | Seward Shawn      |
      | owner               | Acme inc.         |

    Scenario: A solution owner can edit only its own solutions.
      When I am logged in as "Yancy Burton"
      And I go to the homepage of the "Collection example" collection
      And I click "Add solution"
      Then I should see the heading "Add Interoperability Solution"
      When I fill in the following:
        | Title             | Solution A   |
        | Description       | First letter |
      And I attach the file "logo.png" to "Logo"
      And I attach the file "banner.jpg" to "Banner"
      # Click the button to select an existing contact information.
      And I press "Add existing"
      And I fill in "Contact information" with "Seward Shawn"
      And I press "Add contact information"
      # Click the button to select an existing owner.
      And I press "Add existing owner"
      And I fill in "Owner" with "Acme inc."
      And I press "Add owner"
      And I press "Save"
      Then I should see the heading "Solution A"

      And I should see the link "Edit"
      When I go to the "Solution A" solution edit form
      Then I should see the heading "Edit Interoperability Solution Solution A"
      And the following fields should be present "Title, Description, Documentation, Related Solutions, Moderated, Landing page, Metrics page"
      And the following fields should not be present "Issue tracker, Wiki"
      And the following field widgets should be present "Contact information, Owner, eLibrary creation"
      # Logo and banner fields are required, so they are filled up during
      # the creation of the solution. Unfortunately, file fields with a file
      # already attached cannot be found by named xpath, so we look for the
      # related labels.
      And I should see the text "Logo"
      And I should see the text "Banner"
      When I fill in "Title" with "Solution A revised"
      And I press "Save"
      Then I should see the heading "Solution A revised"

      # This user is an owner only of Solution A.
      When I go to the homepage of the "Another solution" solution
      Then I should not see the link "Edit"
      When I go to the "Another solution" solution edit form
      Then I should get an access denied error

      # Clean up the solution that was created through the UI.
      Then I delete the "Solution A revised" solution

    Scenario: A solution facilitator can edit only the solutions he's associated with.
      Given the following solution:
        | title               | Solution B    |
        | description         | Second letter |
        | logo                | logo.png      |
        | banner              | banner.jpg    |
        | contact information | Seward Shawn  |
        | owner               | Acme inc.     |
      When I am logged in as a facilitator of the "Solution B" solution
      And I go to the homepage of the "Solution B" solution
      Then I should see the link "Edit"
      When I go to the "Solution B" solution edit form
      Then I should see the heading "Edit Interoperability Solution Solution B"
      And the following fields should be present "Title, Description, Documentation, Related Solutions, Moderated, Landing page, Metrics page"
      And the following fields should not be present "Issue tracker, Wiki"
      And the following field widgets should be present "Contact information, Owner, eLibrary creation"
      And I should see the text "Logo"
      And I should see the text "Banner"
      When I fill in "Title" with "Solution B revised"
      And I press "Save"
      Then I should see the heading "Solution B revised"

      When I go to the homepage of the "Another solution" solution
      Then I should not see the link "Edit"
      When I go to the "Another solution" solution edit form
      Then I should get an access denied error
