@api
Feature: Solution editing.
  In order to manage solutions
  As a solution owner or solution facilitator
  I need to be able to edit solutions through UI.

  Background:
    Given the following contact:
      | name  | Seward Shawn       |
      | email | seward@example.com |
    And owner:
      | name      | type    |
      | Acme inc. | Company |
    And users:
      | Username     | E-mail                   |
      | Yancy Burton | yancy.burton@example.com |
    And collection:
      | title | Collection example |
      | state | validated          |
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
      | state               | validated         |

  @terms
  Scenario: A solution owner can edit only its own solutions.
    When I am logged in as "Yancy Burton"
    And I go to the homepage of the "Collection example" collection
    And I click "Add solution"
    Then I should see the heading "Add Solution"
    When I fill in the following:
      | Title          | Solution A         |
      | Description    | First letter       |
      | Name           | Yancy Burton       |
      | E-mail address | yancyb@example.com |
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I fill in "Language" with "http://publications.europa.eu/resource/authority/language/VLS"
    And I select "EU and European Policies" from "Policy domain"
    And I select "[ABB8] Citizen" from "Solution type"

    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Acme inc."
    And I press "Add owner"
    And I press "Propose"
    # Then print last response
    Then I should see the heading "Solution A"

    And I should see the link "Edit"
    When I go to the "Solution A" solution edit form
    Then I should see the heading "Edit Solution Solution A"
    Then the fields "Logo, Banner, Upload a new file or enter a URL, Spatial coverage, Keywords, Related Solutions, Status, Languages, Landing page, Metrics page" should be correctly ordered in the region "Management solution vertical tab"
    Then the fields "Title, Description, Contact information, Policy domain, Owner, Solution type, Moderated, eLibrary creation" should be correctly ordered in the region "Main solution vertical tab"

    And the following fields should not be present "Issue tracker, Wiki, Langcode, Translation"
    And the following fieldsets should be present "Contact information, Owner, eLibrary creation"
    # Logo and banner fields are required, so they are filled up during
    # the creation of the solution. Unfortunately, file fields with a file
    # already attached cannot be found by named xpath, so we look for the
    # related labels.
    And I should see the text "Logo"
    And I should see the text "Banner"

    When I fill in "Title" with "Solution A revised"
    And I press "Propose"
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
      | title               | Solution B     |
      | description         | Second letter  |
      | logo                | logo.png       |
      | banner              | banner.jpg     |
      | contact information | Seward Shawn   |
      | owner               | Acme inc.      |
      | state               | validated      |
      | solution type       | [ABB8] Citizen |
    When I am logged in as a facilitator of the "Solution B" solution
    And I go to the homepage of the "Solution B" solution
    Then I should see the link "Edit"
    When I go to the "Solution B" solution edit form
    Then I should see the heading "Edit Solution Solution B"

    When I go to the homepage of the "Another solution" solution
    Then I should not see the link "Edit"
    When I go to the "Another solution" solution edit form
    Then I should get an access denied error
