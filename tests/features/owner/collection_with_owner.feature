@api
Feature: Creation of owners through UI
  In order to manage owners
  As a user
  I need to be able to create owners, or add existing, through the UI when proposing a collection.

  @terms
  Scenario: Propose a collection
    Given the following owner:
      | name            | type    |
      | My organisation | Company |
    And I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    Then the following field widgets should be present "Contact information, Owner"
    When I fill in the following:
      | Title         | Classical and Ancient Mythology                                                                      |
      | Description   | The seminal work on the ancient mythologies of the primitive and classical peoples of the Discworld. |
    When I select "EU and European Policies" from "Policy domain"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"

    # Click the button to create an organisation owner.
    And I press "Add new" at the "Owner" field
    # Since it is a 'propose' form, the field is not shown for the parent either.
    # It is safe to check that the field is not found in the entire form.
    Then the following fields should not be present "Current workflow state"
    When I set the Owner type to "Company"
    And I fill in "Name" with "Acme"
    And I press "Create owner"
    Then I should see "Acme"
    And I should see the link "Company"

    # Edit.
    When I press "Edit" at the "Owner" field
    And I set the Owner type to "Industry consortium,Company"
    And I fill in "Name" with "Acme Inc."
    Then I press "Update owner"
    Then I should see "Acme Inc."
    And I should see the link "Industry consortium"
    And I should see the link "Company"

    When I press "Remove" at the "Owner" field
    And I press "Remove" at the "Owner" field
    Then I should not see "Acme Inc."
    And I should not see the link "Industry consortium"

    # Create a person owner as well.
    And I press "Add new" at the "Owner" field
    And I set the Owner type to "Private Individual(s)"
    And I fill in "Name" with "John Doe"
    And I press "Create owner"
    Then I should see "John Doe"
    And I should see the link "Private Individual(s)"

    When I press "Remove" at the "Owner" field
    # Press 'Remove' also on confirmation dialog.
    And I press "Remove" at the "Owner" field
    Then I should not see "John Doe"
    And I should not see the link "Private Individual(s)"

    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "My organisation"
    And I press "Add owner"
    And I press "Save"

    Then I should see the heading "Classical and Ancient Mythology"

    # Clean up the collection that was created.
    Then I delete the "Classical and Ancient Mythology" collection
