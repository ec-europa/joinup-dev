@api @group-f
Feature: User interface for the File URL field
  In order to be able to upload files or refer to external files
  As an authenticated user
  I need to be able to interact with a field widget that offers both options

  @javascript
  Scenario: Interact with both the 'File upload' and 'External URL' options
    Given the following collection:
      | title | Caterpillar, use headbutt |
      | logo  | logo.png                  |
      | state | validated                 |

    Given I am logged in as a member of the "Caterpillar, use headbutt" collection
    # An example of the File URL field is the "Documentation" field in the
    # solution form.
    And I go to the add solution form of the "Caterpillar, use headbutt" collection

    # Initially, only the radio button selector should be visible, but no option
    # is selected.
    Then the "Upload file" radio button should not be selected
    And the "Remote file URL" radio button should not be selected
    But the following fields should not be visible "Choose a file,Remote URL"
    And I should not see the text "Allowed types: txt doc docx pdf."
    And I should not see the description "This must be an external URL such as http://example.com." for "Upload a new file or enter a URL"
    And I click "Additional fields"
    # Try to upload a file.
    Given I select the radio button "Upload file"
    Then the following field should be visible "Choose a file"
    And I should see the text "Allowed types: txt doc docx pdf."
    But the following field should not be visible "Remote URL"
    And I should not see the description "This must be an external URL such as http://example.com." for "Upload a new file or enter a URL"

    # Toggle the option. Now the other field and help text should be visible.
    Given I select the radio button "Remote file URL"
    Then the following field should be visible "Remote URL"
    And I should see the description "This must be an external URL such as http://example.com." for "Upload a new file or enter a URL"
    But the following field should not be visible "Choose a file"
    And I should not see the text "Allowed types: txt doc docx pdf."
