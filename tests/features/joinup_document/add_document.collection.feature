@api
Feature: "Add document" visibility options.
  In order to manage documents
  As a collection member
  I need to be able to add "Document" content through UI.

  Scenario: "Add document" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following collections:
      | title                   | logo     | banner     | state     |
      | Ring of Truth           | logo.png | banner.jpg | validated |
      | The Storms of the Waves | logo.png | banner.jpg | validated |

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Ring of Truth" collection
    Then I should not see the link "Add document"

    When I am an anonymous user
    And I go to the homepage of the "Ring of Truth" collection
    Then I should not see the link "Add document"

    When I am logged in as a member of the "Ring of Truth" collection
    And I go to the homepage of the "Ring of Truth" collection
    Then I should see the link "Add document"

    When I am logged in as a "facilitator" of the "Ring of Truth" collection
    And I go to the homepage of the "Ring of Truth" collection
    Then I should see the link "Add document"
    # I should not be able to add a document to a different collection
    When I go to the homepage of the "The Storms of the Waves" collection
    Then I should not see the link "Add document"

    When I am logged in as a "moderator"
    And I go to the homepage of the "Ring of Truth" collection
    Then I should see the link "Add document"

  Scenario: Add document as a facilitator.
    Given collections:
      | title                | logo     | banner     | state     |
      | Hunter in the Swords | logo.png | banner.jpg | validated |
    And I am logged in as a facilitator of the "Hunter in the Swords" collection

    When I go to the homepage of the "Hunter in the Swords" collection
    And I click "Add document" in the plus button menu
    Then I should see the heading "Add document"
    And the following fields should be present "Title, Short title, Type, Policy domain, Keywords, Spatial coverage, Licence, Description, Upload a new file or enter a URL"
    # The entity is new, so the current workflow state should not be shown.
    And the following fields should not be present "Current workflow state, Motivation"
    And the following fields should not be present "Shared in"

    When I fill in the following:
      | Title       | An amazing document |
      | Short title | Amazing document    |
    And I select "Document" from "Type"
    Then I upload the file "test.zip" to "Upload a new file or enter a URL"
    And I press "Save as draft"
    Then I should see the error message "Description field is required."
    When I enter "This is going to be an amazing document." in the "Description" wysiwyg editor
    And I press "Save as draft"
    Then I should see the heading "An amazing document"
    And I should see the success message "Document An amazing document has been created."
    And I should see the link "test.zip"
    # Check that the link to the document is visible on the collection page.
    When I go to the homepage of the "Hunter in the Swords" collection
    Then I should see the link "An amazing document"

  # Regression test to ensure that no critical errors are thrown when a new
  # revision is created for a document that has a remote file attached.
  # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3670
  Scenario: Remote URLs can be used in documents.
    Given the following collection:
      | title | Strong Lasers |
      | state | validated     |
    And licence:
      | title       | Creative Commons Zero                                               |
      | description | CC0 is a legal tool for waiving as many rights as legally possible. |
      | type        | Public domain                                                       |
    And document content:
      | title       | document type | short title | file type | file                   | body                 | licence               | state     | collection    |
      | Laser types | document      | L-Types     | remote    | http://www.example.com | List of laser types. | Creative Commons Zero | validated | Strong Lasers |

    When I am logged in as a facilitator of the "Strong Lasers" collection
    And I go to the "Laser types" document
    And I click "Edit" in the "Entity actions" region
    And I press "Update"
    Then I should see the success message "Document Laser types has been updated"
