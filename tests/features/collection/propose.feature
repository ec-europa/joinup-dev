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

  @terms
  Scenario: Propose a collection
    Given owner:
      | name                 | type    |
      | Organisation example | Company |
    And I am logged in as an "authenticated user"
    When I am on the homepage
    And I click "Propose collection"
    Then I should see the heading "Propose collection"
    And the following fields should not be present "Current workflow state"
    And the following field widgets should be present "Contact information, Owner"
    When I fill in the following:
      | Title            | Ancient and Classical Mythology                                                                      |
      | Description      | The seminal work on the ancient mythologies of the primitive and classical peoples of the Discworld. |
      | Spatial coverage | Belgium (http://publications.europa.eu/resource/authority/country/BEL)                               |
    When I select "HR" from "Policy domain"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I check "Closed collection"
    And I select "Only members can create new content." from "eLibrary creation"
    And I check "Moderated"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Save as draft"
    Then I should see the heading "Ancient and Classical Mythology"
    # Check that the policy domain is shown.
    And I should see the text "HR"
    And I should see the text "Belgium"

    # The user that proposed the collection should be auto-subscribed.
    And the "Ancient and Classical Mythology" collection should have 1 member
    # The overview and about links should be added automatically in the menu.
    And I should see the link "Overview" in the "Left sidebar" region
    And I should see the link "About" in the "Left sidebar" region
    When I click the contextual link "Add new page" in the "Left sidebar" region
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
      | state | validated              |
    Given I am logged in as a user with the "authenticated" role
    When I am on the homepage
    And I click "Propose collection"
    And I fill in the following:
      | Title       | The Ratcatcher's Guild                                            |
      | Description | A guild of serious men with sacks in which things are struggling. |
    And I attach the file "logo.png" to "Logo"
    And I press "Save as draft"
    Then I should see the error message "Content with title The Ratcatcher's Guild already exists. Please choose a different title."

  @javascript
  # This is a regression test for a bug in which the label texts of the options
  # vanished after performing an AJAX request in a different element on the
  # page.
  # See https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2589
  Scenario: E-library options should not vanish after AJAX request.
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I click the "Description" tab
    And I attach the file "banner.jpg" to "Banner"
    And I wait for AJAX to finish
    Then I should see the link "banner.jpg"
    And I should see the text "Only members can create new content."
    And I should see the text "Any registered user can create new content."

  @javascript
  Scenario: eLibrary creation options should adapt to the state of the 'closed collection' option
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I click the "Description" tab

    # Initially the collection is open, check if the eLibrary options are OK.
    Then the option "Only members can create new content." should be selected
    And the option "Any registered user can create new content." should not be selected
    And I should not see the text "Only collection facilitators can create new content."

    When I move the "eLibrary creation" slider to the right
    Then the option "Any registered user can create new content." should be selected
    And the option "Only members can create new content." should not be selected

    # When toggling to closed, the option 'any registered user' should disappear
    # and the option for facilitators should appear.
    When I check "Closed collection"
    And I wait for AJAX to finish
    Then the option "Only members can create new content." should be selected
    And the option "Only collection facilitators can create new content." should not be selected
    And I should not see the text "Any registered user can create new content."

    # Check if moving the slider selects the correct option. Visually the handle
    # of the slider moves underneath the other option.
    When I move the "eLibrary creation" slider to the right
    Then the option "Only collection facilitators can create new content." should be selected
    And the option "Only members can create new content." should not be selected

    # This is a regression test for a bug in which the both the previous option
    # and the default option were selected after cycling the collection
    # checkbox status open-closed-open-closed.
    # See https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2589
    When I uncheck "Closed collection"
    And I wait for AJAX to finish
    And I check "Closed collection"
    And I wait for AJAX to finish
    Then the option "Only members can create new content." should be selected
    And the option "Only collection facilitators can create new content." should not be selected

  @javascript
  Scenario: Propose collection form fields should be organized in tabs.
    Given I am logged in as an "authenticated user"
    When I go to the propose collection form
    Then the following fields should be visible "Title, Description, Policy domain"
    And the following field widgets should be visible "Owner"
    And the following fields should not be visible "Closed collection, eLibrary creation, Moderated, Abstract, Affiliates, Spatial coverage"
    And the following field widgets should not be visible "Contact information"

    When I click "Description" tab
    Then the following fields should not be visible "Title, Description, Policy domain"
    And the following field widgets should not be visible "Owner"
    And the following fields should be visible "Closed collection, eLibrary creation, Moderated, Abstract, Affiliates, Spatial coverage"
    And the following field widgets should be visible "Contact information"
    # There should be a help text for the 'Access URL' field.
    Then I should see the description "This must be an external URL such as http://example.com." for the "Access URL" field

  @javascript @terms
  # This is a regression test for a bug where nothing was happening when
  # submitting the collection form after not filling some of the required
  # fields. This was due the HTML5 constraint validation not being able to
  # focus the wanted element because it was hidden by css.
  # See https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3057
  Scenario: Browser validation errors should focus the correct field group.
    Given I am logged in as an "authenticated user"
    When I go to the propose collection form
    Then the "Main" tab should be active
    # This form has two elements only that have browser-side validation.
    When I fill in "Title" with "Constraint validation API"
    And I click the "Description" tab
    And I press "Propose"
    # Our code should have changed the active tab now. A browser message will
    # be shown to the user.
    Then the "Main" tab should be active
    # Fill the required field.
    When I select "HR" from "Policy domain"
    And I press "Propose"
    # The backend-side validation will kick in now.
    Then I should see the error message "Description field is required."
