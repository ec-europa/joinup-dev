@api @group-g
Feature: Creation of contact information
  In order to add contact information
  As a facilitator
  I need to be able to enter contact information

  Scenario: Create a contact information
    Given I am logged in as an "authenticated user"
    And I go to the propose collection form
    # Check that the help text for the website field is visible.
    Then I should see the description "This must be an external URL such as http://example.com." for the "Website URL" field

    When I press "Create contact information"
    Then I should see the following error messages:
      | error messages                    |
      | Name field is required.           |
      | E-mail address field is required. |

    When I fill in the following:
      | E-mail | foo@bar                     |
      | Name   | Contact information example |
      | URL    | http://www.example.org      |
    And I press "Create contact information"
    Then the following fields should not be present "Langcode, Translation"
    Then I should see the error message "The e-mail foo@bar is not valid."
    When I fill in "E-mail address" with "foo@bar.com"
    And I press "Create contact information"
    Then I should see the text "Contact information example"
