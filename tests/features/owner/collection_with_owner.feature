@api
Feature: Creation of owners through UI
  In order to manage owners
  As a user
  I need to be able to create owners, or add existing, through the UI when proposing a collection.

  Scenario: Propose a collection
    Given the following organisation:
      | name | My organisation |
    And I am logged in as a user with the "authenticated" role
    When I am on the homepage
    And I click "Propose collection"
    Then the following field widgets should be present "Contact information, Owner"
    When I fill in the following:
      | Title         | Classical and Ancient Mythology                                                                      |
      | Description   | The seminal work on the ancient mythologies of the primitive and classical peoples of the Discworld. |
      | Policy domain | Environment (WIP!) (http://joinup.eu/policy-domain/environment)                                      |
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"

    # Click the button to create an organisation owner.
    And I press "Add new owner" at the "Owner" field
    And I fill in "Name" with "Organisation example 2"
    And I press "Create owner"

    # Create a person owner as well.
    # There is no label for the bundle type so we have to provide the machine name "field_ar_owner[actions][bundle]".
    # field_ar_owner[actions][bundle] is the select field where the user selects "person" or "organisation".
    And I select "Person" in the dropdown of the "Owner" field
    And I press "Add new owner" at the "Owner" field
    And I fill in "Name" with "Person created example"
    And I press "Create owner"

    # Click the button to select an existing owner.
    And I press "Add existing owner" at the "Owner" field
    And I fill in "Owner" with "My organisation"
    And I press "Add owner"
    And I press "Save"
    Then I should see the heading "Classical and Ancient Mythology"

    # Clean up the collection that was created.
    Then I delete the "Classical and Ancient Mythology" collection
    Then I delete the "Person created example" person
    Then I delete the "Organisation example 2" organisation