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
    And I click "Add document"
    Then I should see the heading "Add document"
    And the following fields should be present "Title, Short title, Type, Policy domain, Keywords, Spatial coverage, Licence, Description, File"

    # The sections about managing revisions and groups should not be visible.
    And I should not see the text "Revision information"
    And the following fields should not be present "Groups audience, Other groups, Create new revision, Revision log message"

    When I fill in the following:
      | Title       | An amazing document                      |
      | Short title | Amazing document                         |
      | Description | This is going to be an amazing document. |
    And I select "Document" from "Type"
    And I attach the file "test.zip" to "File"
    And I press "Save as draft"
    Then I should see the heading "An amazing document"
    And I should see the success message "Document An amazing document has been created."
    # Check that the link to the document is visible on the collection page.
    When I go to the homepage of the "Hunter in the Swords" collection
    Then I should see the link "An amazing document"
