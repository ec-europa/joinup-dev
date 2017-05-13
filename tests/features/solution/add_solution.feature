@api
Feature: "Add solution" visibility options.
  In order to manage solutions
  As a moderator
  I need to be able to add "Solution" rdf entities through UI.

  Scenario: "Add solution" button should only be shown to moderators and facilitators.
    Given the following collection:
      | title | Collection solution test |
      | logo  | logo.png                 |
      | state | validated                |

    When I am logged in as a "facilitator" of the "Collection solution test" collection
    And I go to the homepage of the "Collection solution test" collection
    Then I should see the link "Add solution"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Collection solution test" collection
    Then I should not see the link "Add solution"

    When I am an anonymous user
    And I go to the homepage of the "Collection solution test" collection
    Then I should not see the link "Add solution"

  Scenario: "Propose solution" button should be shown to everyone.
    Given the following collection:
      | title | Collection propose solution test |
      | logo  | logo.png                         |
      | state | validated                        |

    When I am an anonymous user
    And I go to the homepage
    Then I should see the link "Propose solution"
    When I go to the homepage of the "Collection propose solution test" collection
    Then I should see the link "Propose solution"
    When I click "Propose solution"
    # Anonymous users are prompted to login.
    Then I should see the error message "Access denied. You must log in to view this page."

    When I am logged in as an "authenticated user"
    And I go to the homepage
    Then I should see the link "Propose solution"
    When I go to the homepage of the "Collection propose solution test" collection
    Then I should see the link "Propose solution"
    When I click "Propose solution"
    # Authenticated users can propose solutions.
    Then I should not see the heading "Access denied"

    When I am logged in as a user with the "moderator" role
    And I go to the homepage
    Then I should see the link "Propose solution"
    When I go to the homepage of the "Collection propose solution test" collection
    Then I should see the link "Propose solution"
    When I click "Propose solution"
    # Authenticated users can propose solutions.
    Then I should not see the heading "Access denied"

    When I am logged in as a "facilitator" of the "Collection propose solution test" collection
    And I go to the homepage
    Then I should see the link "Propose solution"
    When I go to the homepage of the "Collection propose solution test" collection
    # For facilitators of a collection, the button changes to 'Add solution'.
    Then I should not see the link "Propose solution"
    But I should see the link "Add solution"

  @terms
  Scenario: Add solution as a collection facilitator.
    Given the following collection:
      | title | Belgian barista's |
      | logo  | logo.png          |
      | state | validated         |
    And the following contact:
      | email | foo@bar.com                 |
      | name  | Contact information example |
    And the following owner:
      | name                 | type                         |
      | Organisation example | Company, Industry consortium |
    And I am logged in as a facilitator of the "Belgian barista's" collection

    When I go to the homepage of the "Belgian barista's" collection
    And I click "Add solution"
    Then I should see the heading "Add Solution"
    And the following fields should be present "Title, Description, Documentation, Logo, Banner"
    And the following fields should not be present "Groups audience, Other groups, Current workflow state"
    When I fill in the following:
      | Title            | Espresso is the solution                                      |
      | Description      | This is a test text                                           |
      | Spatial coverage | Belgium                                                       |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS |
    Then I select "http://data.europa.eu/eira/TestScenario" from "Solution type"
    And I select "Demography" from "Policy domain"
    # Attach a PDF to the documentation, this has a hidden label "File".
    And I attach the file "text.pdf" to "File"
    # Click the button to select an existing contact information.
    And I press "Add existing" at the "Contact information" field
    And I fill in "Contact information" with "Contact information example"
    And I press "Add contact information"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    # Ensure that the Status field is a dropdown.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3342
    And I select "Completed" from "Status"
    And I press "Propose"
    # Regression test for non required fields 'Banner' and 'Logo'.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3328
    Then I should not see the following error messages:
      | Banner field is required. |
      | Logo field is required.   |
    But I should see a logo on the header
    And I should see a banner on the header
    And I should see the heading "Espresso is the solution"
    When I am logged in as a moderator
    When I go to the "Espresso is the solution" solution edit form
    And I press "Publish"
    # The name of the solution should exist in the block of the relative content in a collection.
    Then I should see the heading "Espresso is the solution"
    # The solution fields will be shown in the "about" page.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3224
    And I should not see the text "This is a test text"
    And I should not see the link "Demography"
    And I should not see the link "Belgium"
    And I should not see the link "Flemish"

    When I am logged in as a facilitator of the "Belgian barista's" collection
    # Make sure that when another solution is added, both are affiliated.
    When I go to the homepage of the "Belgian barista's" collection
    And I click "Add solution"
    When I fill in the following:
      | Title            | V60 filter coffee solution                                             |
      | Description      | This is a test text                                                    |
      | Spatial coverage | Belgium (http://publications.europa.eu/resource/authority/country/BEL) |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS          |
    Then I select "http://data.europa.eu/eira/TestScenario" from "Solution type"
    And I select "E-inclusion" from "Policy domain"
    # Attach a PDF to the documentation, this has a hidden label "File".
    And I attach the file "text.pdf" to "File"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing contact information.
    And I press "Add existing" at the "Contact information" field
    And I fill in "Contact information" with "Contact information example"
    And I press "Add contact information"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    And I press "Propose"
    Then I should see the heading "V60 filter coffee solution"
    # The name of the solution should exist in the block of the relative content in a collection.
    When I go to the homepage of the "Belgian barista's" collection
    Then I should see the heading "Belgian barista's"
    Then I should see the link "Espresso is the solution"
    Then I should see the link "V60 filter coffee solution"

    # Clean up the solution that was created through the UI.
    Then I delete the "V60 filter coffee solution" solution
    Then I delete the "Espresso is the solution" solution

  @terms
  Scenario: Correct transition buttons are shown after a partial filled form is submitted.
    # This is a regression test for a bug that was causing the owner state
    # buttons to leak into the solution create form, causing critical errors
    # when an invalid state button was pressed.
    # @see issue ISAICP-3209
    Given the following collection:
      | title | Language parsers |
      | state | validated        |
    When I am logged in as a facilitator of the "Language parsers" collection
    And I go to the homepage of the "Language parsers" collection
    And I click "Add solution"
    And I fill in the following:
      | Title       | PHP comments parser                             |
      | Description | A simple parser that goes through PHP comments. |
    And I select "Data gathering, data processing" from "Policy domain"
    And I select "[ABB117] Implementing Guideline" from "Solution type"

    # Fill the owner inline form, but don't submit it.
    And I press "Add new" at the "Owner" field
    And I fill in "Name" with "Azure Tennison"

    # Submit the incomplete form, so error messages about missing fields will
    # be shown.
    When I press "Propose"
    Then I should see the following error messages:
      | error messages                         |
      | Banner field is required.              |
      | Contact information field is required. |
      | Logo field is required.                |
    # Buttons should be shown for the allowed solution transitions.
    And I should see the button "Save as draft"
    And I should see the button "Propose"
    # The owner entity state buttons should not be shown.
    But I should not see the button "Request deletion"
    And I should not see the button "Update"
