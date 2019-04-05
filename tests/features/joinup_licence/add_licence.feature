@api
Feature: Add licence through UI
  In order to manage licences
  As a moderator or a licence manager
  I need to be able to add licences through the UI.


  Scenario Outline: Add licence as a Moderator or a Licence Manager.
    Given I am logged in as a "<role>"
    And I am on the homepage
    # The dashboard link is visible by clicking the user icon.
    When I click "Dashboard"
    And I click "Licences overview"
    And I click "Add licence"
    Then I should see the heading "Add Licence"
    And the "Licence legal type" field should contain the "Can, Must, Cannot, Compatible, Law, Support" option groups
    When I fill in "Title" with "This is a random licence"
    And I fill in "Description" with "Licence details go here.."
    # Ensure that the Type field is a dropdown.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3342
    And I select "Public domain" from "Type"
    And I select "Strong Community" from "Licence legal type"
    And I additionally select "Venue fixed" from "Licence legal type"

    And I press "Save"
    Then I should see the link "Strong Community"
    And I should see the link "Venue fixed"
    Then I should have 1 licence
    When I go to the homepage of the "This is a random licence" licence
    Then I should see the heading "This is a random licence"
    And I should see the link "Public domain"
    # Clean up the licence that was created through the UI.
    Then I delete the "This is a random licence" licence

    Examples:
      | role            |
      | moderator       |
      | licence_manager |
