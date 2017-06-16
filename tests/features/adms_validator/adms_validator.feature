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
      And I press "Upload"

      # Check validations.
      And I should see the text "The mandatory class dcat:Dataset does not exist" in the "dcat:Dataset does not exist." row

      # Check incorrect file.
      When I attach the file "empty.rdf" to "File"
      And I press "Upload"
      Then I should see the following warning messages:
        | The provided file is not a valid RDF file. |
