@api
Feature: Creation of contact information
  In order to add contact information
  As a facilitator
  I need to be able to enter contact information

  Scenario: Create a contact information
    Given I am logged in as an "authenticated user"
    When I click "Propose collection" in the plus button menu
    And I click the "Description" tab
    And I press "Add new" at the "Contact information" field
    When I fill in the following:
      | E-mail | foo@bar                     |
      | Name   | Contact information example |
      | URL    | http://www.example.org      |
    And I press "Create contact information"
    Then I should see the error message "The e-mail foo@bar is not valid."
    When I fill in "E-mail address" with "foo@bar.com"
    And I press "Create contact information"
    Then I should see the text "Contact information example"
