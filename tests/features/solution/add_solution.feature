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
      | name                 | type    |
      | Organisation example | Company, Industry consortium |
    And I am logged in as a facilitator of the "Belgian barista's" collection

    When I go to the homepage of the "Belgian barista's" collection
    And I click "Add solution"
    Then I should see the heading "Add Solution"
    And the following fields should be present "Title, Description, Documentation, Logo, Banner, Current workflow state"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in the following:
      | Title            | Espresso is the solution                                               |
      | Description      | This is a test text                                                    |
      | Spatial coverage | Belgium (http://publications.europa.eu/resource/authority/country/BEL) |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS          |
    Then I select "http://data.europa.eu/eira/TestScenario" from "Solution type"
    And I select "Demography" from "Policy domain"
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
    When I am logged in as a moderator
    When I go to the "Espresso is the solution" solution edit form
    And I press "Publish"
    # The name of the solution should exist in the block of the relative content in a collection.
    Then I should see the heading "Espresso is the solution"
    And I should see the text "This is a test text"
    And I should see the link "Belgian barista's"
    And I should see the link "Demography"
    And I should see the link "Belgium"
    And I should see the link "Flemish"
    When I click "Belgian barista's"
    Then I should see the heading "Belgian barista's"

    Then I should see the link "Espresso is the solution"

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
    # The name of the solution should exist in the block of the relative content in a collection.
    Then I should see the heading "V60 filter coffee solution"
    When I click "Belgian barista's"
    Then I should see the heading "Belgian barista's"
    Then I should see the link "Espresso is the solution"
    Then I should see the link "V60 filter coffee solution"

    # Clean up the solution that was created through the UI.
    Then I delete the "V60 filter coffee solution" solution
    Then I delete the "Espresso is the solution" solution
