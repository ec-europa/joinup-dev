@api
Feature: "Add document" visibility options.
  In order to manage documents
  As a solution member
  I need to be able to add "Document" content through UI.

  Scenario: "Add document" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following solutions:
      | title                   | logo     | banner     |
      | Seventh Name           | logo.png | banner.jpg |
      | The Obsessed Stream | logo.png | banner.jpg |

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Seventh Name" solution
    Then I should not see the link "Add document"

    When I am an anonymous user
    And I go to the homepage of the "Seventh Name" solution
    Then I should not see the link "Add document"

    When I am logged in as a "facilitator" of the "Seventh Name" solution
    And I go to the homepage of the "Seventh Name" solution
    Then I should see the link "Add document"
    # I should not be able to add a document to a different solution
    When I go to the homepage of the "The Obsessed Stream" solution
    Then I should not see the link "Add document"

    When I am logged in as a "moderator"
    And I go to the homepage of the "Seventh Name" solution
    Then I should see the link "Add document"

  Scenario: Add document as a facilitator.
    Given solutions:
      | title                | logo     | banner     |
      | Winter of Beginning | logo.png | banner.jpg |
    And I am logged in as a facilitator of the "Winter of Beginning" solution

    When I go to the homepage of the "Winter of Beginning" solution
    And I click "Add document"
    Then I should see the heading "Add document"
    And the following fields should be present "Title, Short title, Description, File, Source URL"
    And the following fields should not be present "Groups audience"
    When I fill in the following:
      | Title       | The Sparks of the Butterfly                      |
      | Short title | Amazing document                         |
      | Description | This is going to be an amazing document. |
    # Ensure that validation for one of the file source is properly occurring.
    And I press "Save"
    Then I should see the error message "One of the file sources must be filled."
    When I attach the file "test.zip" to "File"
    And I press "Save"
    Then I should see the heading "The Sparks of the Butterfly"
    And I should see the success message "Document The Sparks of the Butterfly has been created."
    And the "Winter of Beginning" solution has a document titled "The Sparks of the Butterfly"
    # Check that the link to the document is visible on the solution page.
    When I go to the homepage of the "Winter of Beginning" solution
    And I click "The Sparks of the Butterfly"
