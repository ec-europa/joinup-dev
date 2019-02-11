@api
Feature: "Add document" visibility options.
  In order to manage documents
  As a solution member
  I need to be able to add "Document" content through UI.

  Scenario: "Add document" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following collection:
      | title  | Collective Seventh Name |
      | logo   | logo.png                |
      | banner | banner.jpg              |
      | state  | validated               |
    And the following solutions:
      | title               | collection              | logo     | banner     | state     |
      | Seventh Name        | Collective Seventh Name | logo.png | banner.jpg | validated |
      | The Obsessed Stream | Collective Seventh Name | logo.png | banner.jpg | validated |

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
    Given the following collection:
      | title  | Collective Winter of Beginning |
      | logo   | logo.png                       |
      | banner | banner.jpg                     |
      | state  | validated                      |
    And solutions:
      | title               | collection                     | logo     | banner     | state     |
      | Winter of Beginning | Collective Winter of Beginning | logo.png | banner.jpg | validated |
    And I am logged in as a facilitator of the "Winter of Beginning" solution

    When I go to the homepage of the "Winter of Beginning" solution
    And I click "Add document" in the plus button menu
    Then I should see the heading "Add document"
    And the following fields should be present "Title, Short title, Type, Policy domain, Keywords, Spatial coverage, Licence, Description, Upload a new file or enter a URL"
    And the following fields should not be present "Shared in, Motivation"

    When I fill in the following:
      | Title       | The Sparks of the Butterfly              |
      | Short title | Amazing document                         |
    And I select "Document" from "Type"
    Then I upload the file "test.zip" to "Upload a new file or enter a URL"
    And I press "Save as draft"
    Then I should see the error message "Description field is required."
    When I enter "This is going to be an amazing document." in the "Description" wysiwyg editor
    And I press "Save as draft"
    Then I should see the heading "The Sparks of the Butterfly"
    And I should see the success message "Document The Sparks of the Butterfly has been created."
    And I should see the link "test.zip"
    And the "Winter of Beginning" solution has a document titled "The Sparks of the Butterfly"
    # Check that the link to the document is visible on the solution page.
    When I go to the homepage of the "Winter of Beginning" solution
    Then I should see the link "The Sparks of the Butterfly"
