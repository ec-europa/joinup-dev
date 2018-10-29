@api
Feature: Validate an ADMS-AP file through the UI
  In order to be sure that my ADMS data is compliant
  As a user
  I need to be able to validate an RDF file

  Scenario: Validate a file
    When I am an anonymous user
    And I go to the homepage
    Then I should see the link "ADMS-AP Validator"

    When I click "ADMS-AP Validator"
    Then I should see the heading "ADMS-AP Validator"

    When I attach the file "invalid_adms.rdf" to "File"
    And I wait for the honeypot validation to pass
    And I press "Upload"
    Then I should see the text "16 schema error(s) were found while validating."
    And I should see the text "The dcat:Dataset http://example.com/solution/1 does not have a dcat:contactPoint property." in the "dcat:contactPoint is a required property for dcat:Dataset." row

      # Check file non-compliant to ADMS v2.
    When I attach the file "valid_adms.rdf" to "File"
    And I wait for the honeypot validation to pass
    And I press "Upload"
    Then I should see the text "No errors found during validation."

      # Check incorrect file.
    When I attach the file "empty.rdf" to "File"
    And I wait for the honeypot validation to pass
    And I press "Upload"
    Then I should see the following error messages:
      | error messages                             |
      | The provided file is not a valid RDF file. |
