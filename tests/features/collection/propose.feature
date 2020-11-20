@api @group-a
Feature: Proposing a collection
  In order to create a new collection on Joinup
  As the product owner of a collection of software solutions
  I need to be able to propose a collection for inclusion on Joinup

  # An anonymous user should be shown the option to add a collection, so that
  # the user will be aware that collections can be added by the public, even
  # though you need to sign in to do so.
  Scenario: Anonymous user needs to sign in before creating a collection
    Given users:
      | Username      | E-mail           |
      | Cecil Clapman | cecil@example.eu |
    Given CAS users:
      | Username | E-mail                | Password  | First name | Last name | Local username |
      | cclapman | clapman@ec.example.eu | abc123!#$ | Cecil J    | Clapman   | Cecil Clapman  |
    Given I am an anonymous user
    When I go to the propose collection form
    Then I should see the heading "Sign in to continue"
    When I fill in the following:
      | E-mail address | clapman@ec.example.eu |
      | Password       | abc123!#$             |
    And I press "Log in"
    Then I should see the heading "Propose challenge"

  @terms
  Scenario: Propose a collection
    Given owner:
      | name                 | type    |
      | Organisation example | Company |
    And I am logged in as an "authenticated user"
    When I go to the propose collection form
    Then I should see the heading "Propose challenge"
    And the following fields should not be present "Current workflow state, Langcode, Translation, Motivation"
    And the following field widgets should be present "Contact information, Owner"
    # Ensure that the description for the "Access url" is shown.
    # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3196
    And I should see the description "Web page for the external Repository." for the "Access URL" field
    And I should see the description "This must be an external URL such as http://example.com." for the "Access URL" field
    And I should see the description "For best result the image must be larger than 2400x345 pixels." for the "Banner" field

    # Check that validations errors are shown for required fields.
    When I press "Propose"
    Then I should see the following error messages:
      | error messages                    |
      | Title field is required.          |
      | Description field is required.    |
      | Domains field is required.        |
      | Owner field is required.          |
      | Name field is required.           |
      | E-mail address field is required. |

    When I fill in the following:
      | Title                 | Ancient and Classical Mythology                                                                      |
      | Description           | The seminal work on the ancient mythologies of the primitive and classical peoples of the Discworld. |
      | Geographical coverage | Belgium                                                                                              |
      # Contact information data.
      | Name                  | Contact person                                                                                       |
      | E-mail                | contact_person@example.com                                                                           |
    When I select "HR" from "Domains"
    And I select the radio button "Only members can create content."
    And I check "Moderated"
    # The owner field should have a help text.
    And I should see the text "The Owner is the organisation that owns this entity and is the only responsible for it."
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Propose"
    # Regression test for setting the Logo and Banner fields as optional.
    # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3215
    Then I should not see the following error messages:
      | error messages           |
      | Field Logo is required   |
      | Field Banner is required |
    And I should see the heading "Ancient and Classical Mythology"
    And I should see a logo on the header
    And I should see a banner on the header
    And I should see "Thank you for proposing a challenge. Your request is currently pending approval by the site administrator."

    # The user that proposed the collection should be auto-subscribed.
    And the "Ancient and Classical Mythology" collection should have 1 active member
    # The overview and about links should be added automatically in the menu.
    And I should see the following group menu items in the specified order:
      | text     |
      | Overview |
      | Members  |
      | About    |
    When I click the contextual link "Add new page" in the "Left sidebar" region
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | About                                                          |
      | Body  | <p>Some more<em>information</em><br />about the collection.<p> |
    And I press "Save"
    Then I should see the success message "Custom page About has been created."
    And the page should contain the html text "<p>Some more<em>information</em><br>about the collection.</p>"

    # Clean up the collection that was created.
    Then I delete the "Ancient and Classical Mythology" collection
    And I delete the "Contact person" contact information

  Scenario: Propose a collection with a duplicate name
    Given the following collection:
      | title | The Ratcatcher's Guild |
      | state | validated              |
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in the following:
      | Title       | The Ratcatcher's Guild                                            |
      | Description | A guild of serious men with sacks in which things are struggling. |
      # Contact information data.
      | Name        | Some contact                                                      |
      | E-mail      | some.contact@example.com                                          |
    And I press "Save as draft"
    Then I should see the error message "Content with title The Ratcatcher's Guild already exists. Please choose a different title."

  @javascript
  # This is a regression test for a bug in which the label texts of the options
  # vanished after performing an AJAX request in a different element on the
  # page.
  # See https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-2589
  Scenario: Content creation options should not vanish after AJAX request.
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I click the "Additional fields" tab
    And I attach the file "banner1.jpg" to "Banner"
    And I wait for AJAX to finish
    Then I should see the link "banner1.jpg"
    And I should see the text "Only members can create content."
    And I should see the text "Any user can create content."

  @javascript
  Scenario: Propose collection form fields should be organized in tabs.
    Given I am logged in as an "authenticated user"
    When I go to the propose collection form
    Then the following fields should be visible "Title, Description, Domains"
    And the following field widgets should be visible "Owner"
    And the following fields should not be visible "Moderated, Abstract, Content creation, Geographical coverage"
    And the following fields should not be present "Affiliates"
    And the following field widgets should be visible "Contact information"

    When I click "Additional fields" tab
    Then the following fields should not be visible "Title, Description, Domains"
    And the following field widgets should not be visible "Owner"
    And the following fields should be visible "Content creation, Moderated, Abstract, Geographical coverage"
    And the following fields should not be present "Affiliates"
    And the following field widgets should not be visible "Contact information"

  @javascript @terms
  # This is a regression test for a bug where nothing was happening when
  # submitting the collection form after not filling some of the required
  # fields. This was due the HTML5 constraint validation not being able to
  # focus the wanted element because it was hidden by css.
  # See https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3057
  Scenario: Browser validation errors should focus the correct field group.
    Given I am logged in as an "authenticated user"
    When I go to the propose collection form
    Then the "Main fields" tab should be active
    # This form has two elements only that have browser-side validation.
    When I fill in "Title" with "Constraint validation API"
    And I click the "Additional fields" tab
    And I press "Propose"
    # Our code should have changed the active tab now. A browser message will
    # be shown to the user.
    Then the "Main fields" tab should be active
    # Fill the required fields.
    When I select "HR" from "Domain"
    And I fill in the following:
      | Name   | Contact person             |
      | E-mail | contact_person@example.com |
    And I press "Propose"
    # The backend-side validation will kick in now.
    Then I should see the error message "Description field is required."
