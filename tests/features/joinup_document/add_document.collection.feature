@api @group-d
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

  @terms @uploadFiles:test.zip
  Scenario: Add document as a facilitator.
    Given user:
      | Username    | napcheese         |
      | First name  | Eirik             |
      | Family name | Andries           |
      | E-mail      | eandr@example.com |
    And collections:
      | title                | logo     | banner     | state     |
      | Hunter in the Swords | logo.png | banner.jpg | validated |
    And the following collection user membership:
      | collection           | user      | roles       |
      | Hunter in the Swords | napcheese | facilitator |
    # Log in as a facilitator of the "Hunter in the Swords" collection.
    Given I am logged in as napcheese

    When I go to the homepage of the "Hunter in the Swords" collection
    And I click "Add document" in the plus button menu
    Then I should see the heading "Add document"
    And the following fields should be present "Title, Short title, Type, Topic, Keywords, Geographical coverage, Licence, Description, Upload a new file or enter a URL"
    # The entity is new, so the current workflow state should not be shown.
    And the following fields should not be present "Current workflow state, Motivation"
    And the following fields should not be present "Shared on"

    When I fill in the following:
      | Title       | An amazing document |
      | Short title | Amazing document    |
    And I select "Document" from "Type"
    # Regression test: Document is successfully displayed even when a publication date is not set.
    And I clear the date of the "Publication date" widget
    And I clear the time of the "Publication date" widget
    Then I upload the file "test.zip" to "Upload a new file or enter a URL"
    And I press "Save as draft"
    Then I should see the following error messages:
      | error messages                 |
      | Description field is required. |
      | Topic field is required.       |

    When I enter "This is going to be an amazing document." in the "Description" wysiwyg editor
    And I select "EU and European Policies" from "Topic"
    And I press "Save as draft"
    Then I should see the heading "An amazing document"
    And I should see the success message 'Document An amazing document has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Collection it was created in.'
    And I should see the link "test.zip"
    # Check that the full author name is shown instead of the username.
    And I should see the link "Eirik Andries" in the "Content" region
    But I should not see the link "napcheese" in the "Content" region
    # Check that the link to the document is visible on the collection page.
    When I go to the homepage of the "Hunter in the Swords" collection
    Then I should see the link "An amazing document"

    # Check that the publication date field is prefilled with the current time.
    When I go to the homepage of the "Hunter in the Swords" collection
    And I click "Add document" in the plus button menu
    Then I see "Publication date" filled with the current time

  # Regression test to ensure that no critical errors are thrown when a new
  # revision is created for a document that has a remote file attached.
  # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3670
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
