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
    And solutions:
      | title      | description   | logo     | banner     | contact information | owner          |
      | Solution A | First letter  | logo.png | banner.jpg | Seward Shawn        | Avengers       |
      | Solution B | Second letter | logo.png | banner.jpg | Hedley Gardner      | Justice League |
    And the following solution user memberships:
      | solution   | user           | roles                      |
      | Solution A | Yancy Burton   | administrator, facilitator |
      | Solution B | Nikolas Dalton | facilitator                |

    Scenario: A solution owner can edit only its own solutions.
      When I am logged in as "Yancy Burton"
      And I go to the homepage of the "Solution A" solution
      Then I should see the link "Edit"
      When I go to the "Solution A" solution edit form
      Then I should see the heading "Edit Interoperability Solution Solution A"
      And the following fields should be present "Title, Description, Documentation, Related Solutions, eLibrary creation, Moderated, Landing page, Metrics page, Issue tracker, Wiki"
      And the following field widgets should be present "Contact information, Owner"
      # Logo and banner fields are required, so they are filled up during
      # the creation of the solution to simulate a real case scenario.
      # Unfortunately, file fields with a file already attached cannot be
      # found by named xpath, so we look for the related labels.
      And I should see the text "Logo"
      And I should see the text "Banner"
      When I fill in "Title" with "Solution A revised"
      And I press "Save"
      Then I should see the heading "Solution A revised"

      When I go to the homepage of the "Solution B" solution
      Then I should not see the link "Edit"
      When I go to the "Solution B" solution edit form
      Then I should get an access denied error

    Scenario: A solution facilitator can edit only the solutions he's associated with.
      When I am logged in as "Nikolas Dalton"
      And I go to the homepage of the "Solution B" solution
      Then I should see the link "Edit"
      When I go to the "Solution B" solution edit form
      Then I should see the heading "Edit Interoperability Solution Solution B"
      And the following fields should be present "Title, Description, Documentation, Related Solutions, eLibrary creation, Moderated, Landing page, Metrics page, Issue tracker, Wiki"
      And the following field widgets should be present "Contact information, Owner"
      And I should see the text "Logo"
      And I should see the text "Banner"
      When I fill in "Title" with "Solution B revised"
      And I press "Save"
      Then I should see the heading "Solution B revised"

      When I go to the homepage of the "Solution A" solution
      Then I should not see the link "Edit"
      When I go to the "Solution A" solution edit form
      Then I should get an access denied error
