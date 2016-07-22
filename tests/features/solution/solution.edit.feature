@api
Feature: Solution editing.
  In order to manage solutions
  As a moderator or solution facilitator
  I need to be able to edit solutions through UI.

  Background:
    Given the following contacts:
      | name           | email              |
      | Seward Shawn   | seward@example.com |
      | Hedley Gardner | hedley@example.com |
    And organisations:
      | name           |
      | Avengers       |
      | Justice League |
    And users:
      | name           | mail                       |
      | Yancy Burton   | yancy.burton@example.com   |
      | Nikolas Dalton | nikolas.dalton@example.com |
    And solution:
      | title               | Solution example  |
      | description         | A sample solution |
      | logo                | logo.png          |
      | banner              | banner.jpg        |
      | contact information | Seward Shawn      |
      | owner               | Avengers          |
    And the following solution user memberships:
      | solution         | user           | roles                      |
      | Solution example | Yancy Burton   | administrator, facilitator |
      | Solution example | Nikolas Dalton | facilitator                |

    Scenario: A solution owner can edit only its own solutions.
      When I am logged in as "Yancy Burton"
      And I go to the homepage of the "Solution example" solution
      Then I should see the link "Edit"
      When I go to the "Solution example" solution edit form
      Then I should see the heading "Edit Interoperability Solution Solution example"
      And the following fields should be present "Title, Description, Documentation, Related Solutions, eLibrary creation, Moderated, Landing page, Metrics page, Issue tracker, Wiki"
      And the following field widgets should be present "Contact information, Owner"
      # When files are already uploaded, the named xpath won't find them.
      # Being required, these files need to be filled up during the creation
      # to simulate a real case scenario. Instead of searching the file field,
      # we look for the labels.
      And I should see the text "Logo"
      And I should see the text "Banner"
      When I fill in "Title" with "Solution example revised"
      And I press "Save"
      Then I should see the heading "Solution example revised"
