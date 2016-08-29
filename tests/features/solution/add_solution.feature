@api
Feature: "Add solution" visibility options.
  In order to manage solutions
  As a moderator
  I need to be able to add "Solution" rdf entities through UI.

  Scenario: "Add solution" button should only be shown to moderators and facilitators.
    Given the following collection:
      | title | Collection solution test |
      | logo  | logo.png                 |

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

  Scenario: Add solution as a collection facilitator.
    Given the following collection:
      | title | Collection solution test 2 |
      | logo  | logo.png                   |
    And the following contact:
      | email | foo@bar.com                 |
      | name  | Contact information example |
    And the following organisation:
      | name | Organisation example |
    And I am logged in as a facilitator of the "Collection solution test 2" collection

    When I go to the homepage of the "Collection solution test 2" collection
    And I click "Add solution"
    Then I should see the heading "Add Interoperability Solution"
    And the following fields should be present "Title, Description, Documentation, Logo, Banner"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in the following:
      | Title            | Collection solution add solution                                          |
      | Description      | This is a test text                                                       |
      | Documentation    | text.pdf                                                                  |
      | Policy Domain    | Environment (WIP!) (http://joinup.eu/policy-domain/environment)           |
      | Spatial coverage | Belgium (http://publications.europa.eu/resource/authority/country/BEL)    |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS   |
    Then I select "http://data.europa.eu/eira/TestScenario" from "Solution type"
    And I attach the file "text.pdf" to "Documentation"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing contact information.
    And I press "Add existing" at the "Contact information" field
    And I fill in "Contact information" with "Contact information example"
    And I press "Add contact information"
    # Click the button to select an existing owner.
    And I press "Add existing owner" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    And I press "Save"
    # The name of the solution should exist in the block of the relative content in a collection.
    Then I should see the heading "Collection solution add solution"
    And I should see the text "This is a test text"
    And I should see the link "Collection solution test 2"
    And I should see the link "Environment (WIP!)"
    And I should see the link "Belgium"
    And I should see the link "Flemish"
    When I click "Collection solution test 2"
    Then I should see the heading "Collection solution test 2"
    Then I should see the link "Collection solution add solution"
    # Clean up the solution that was created through the UI.
    Then I delete the "Collection solution add solution" solution
