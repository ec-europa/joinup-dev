@api
Feature: Edit licence through UI
  In order to manage licences
  As a moderator
  I need to be able to edit licences through the UI.

  Scenario: Moderators must be able to view created licences and edit them.
    Given the following licence:
      | title       | Licence 1              |
      | description | Some dummy description |
    When I am logged in as a "moderator"
    And I am on the homepage
    When I click "Dashboard"
    When I click "Licences overview"
    # Check that proper access has been granted.
    Then I should see the heading "Licences"
    And I should see the text "Licence 1"

    # Check that the moderator can edit the licence.
    When I click "Licence 1"
    Then I should see the link "Edit"
    When I click "Edit"
    And I fill in "Title" with "Licence 1.1"
    And I fill in "Description" with "This is some different description."
    And I press "Save"
    Then I should see the heading "Licence 1.1"