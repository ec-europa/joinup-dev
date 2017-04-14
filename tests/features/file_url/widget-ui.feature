@api
Feature: User interface for the File URL field
  In order to be able to upload files or refer to external files
  As an authenticated user
  I need to be able to interact with a field widget that offers both options

  @javascript
  Scenario: Interact with both the 'File upload' and 'External URL' options
    Given I am logged in as an "authenticated user"
    # An example of the File URL field is the "Documentation" field in the
    # solution form.
    And I click "Propose solution" in the plus button menu
    # The "Upload file" option should be selected by default.
    Then the "Upload file" radio button should be selected

    # Check that the upload file field + help text is visible.
    # Note that the label of the upload field is visually hidden with CSS, and
    # the radio button to select this option has the same field label, so we
    # target the CSS selector instead.
    And the following field should be visible "edit-field-is-documentation-0-file-wrap-file-upload"
    And I should see the text "Allowed types: txt doc docx pdf."

    And the following field should not be visible "Remote file"
    And I should not see the description "This must be an external URL such as http://example.com." for "Documentation"

    # Toggle the option. Now the other field and help text should be visible.
    When I select the radio button "Remote file URL"
    Then the following field should be visible "Remote file"
    And I should see the description "This must be an external URL such as http://example.com." for "Documentation"

    # Note that the label of the upload field is visually hidden with CSS, and
    # the radio button to select this option has the same field label, so we
    # target the CSS selector instead.
    And the following field should not be visible "edit-field-is-documentation-0-file-wrap-file-upload"
    And I should not see the text "Allowed types: txt doc docx pdf."
