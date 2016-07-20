@api
Feature: Proposing a collection
  In order to create a new collection on Joinup
  As the product owner of a collection of software solutions
  I need to be able to propose a collection for inclusion on Joinup

  # Todo: It still needs to be decided on which pages the "Propose collection"
  # button will be shown. It might be removed from the homepage in the future.
  # Ref. https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2298

  # An anonymous user should be shown the option to add a collection, so that
  # the user will be aware that collections can be added by the public, even
  # though you need to log in to do so.
  Scenario: Anonymous user needs to log in before creating a collection
    Given users:
    | name          | pass  |
    | Cecil Clapman | claps |
    Given I am an anonymous user
    When I am on the homepage
    And I click "Propose collection"
    Then I should see the error message "Access denied. You must log in to view this page."
    When I fill in the following:
    | Username | Cecil Clapman |
    | Password | claps         |
    And I press "Log in"
    Then I should see the heading "Propose collection"

  Scenario: Propose a collection
    Given the following organisation:
      | name | Organisation example |
    And I am logged in as a user with the "authenticated" role
    When I am on the homepage
    And I click "Propose collection"
    Then I should see the heading "Propose collection"
    And the following field widgets should be present "Contact information, Owner"
    When I fill in the following:
    | Title       | Ancient and Classical Mythology                                                                      |
    | Description | The seminal work on the ancient mythologies of the primitive and classical peoples of the Discworld. |
    # Todo: test adding a "Policy domain" once ISAICP-2356 is done.
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I check "Closed collection"
    And I select "Only members can publish new content" from "eLibrary creation"
    And I check "Moderated"
    # Click the button to select an existing owner.
    And I press "Add existing owner"
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    And I press "Save"
    Then I should see the heading "Ancient and Classical Mythology"
    # The user that proposed the collection should be auto-subscribed.
    And the "Ancient and Classical Mythology" collection should have 1 member
    # There should not be any custom pages in the menu yet, so I should see a
    # button to create a custom page, with accompanying help text.
    # And I should see the text "There are no pages yet. Why don't you start by creating an About page?"
    When I click "Add custom page"
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | About                                       |
      | Body  | Some more information about the collection. |
    And I press "Save"
    Then I should see the success message "Custom page About has been created."

    # Clean up the collection that was created.
    Then I delete the "Ancient and Classical Mythology" collection

  Scenario: Propose a collection with a duplicate name
    Given the following collection:
      | title | The Ratcatcher's Guild |
    Given I am logged in as a user with the "authenticated" role
    When I am on the homepage
    And I click "Propose collection"
    And I fill in the following:
    | Title       | The Ratcatcher's Guild                                            |
    | Description | A guild of serious men with sacks in which things are struggling. |
    And I attach the file "logo.png" to "Logo"
    And I press "Save"
    Then I should see the error message "Content with title The Ratcatcher's Guild already exists. Please choose a different title."
