@api
Feature: As a site moderator I am able to import RDF files.

  Scenario: Test the import RDF files
    Given users:
      | Username        | Roles     |
      | Antoine Batiste | moderator |
      | LaDonna         | moderator |

    Given I am logged in as "Antoine Batiste"
    And I go to the pipeline orchestrator

    When I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"
    Then I should see "Spain - Center for Technology Transfer: Manual upload"

    # Go back to the pipeline selection. You should be redirected to the current
    # active/unfinished step.
    And I go to the pipeline orchestrator
    Then I should see "Spain - Center for Technology Transfer: Manual upload"

    # Test the wizard reset.
    Given I click "Cancel"
    Then I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"

    # The pipeline should be locked for different user.
    Given I am logged in as LaDonna
    And I go to the pipeline orchestrator
    When I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"
    Then I should see the following error messages:
      | error messages                                                                                                                                                                               |
      | Spain - Center for Technology Transfer failed to start. Reason: There's another ongoing import process run by other user. You cannot run 'Spain - Center for Technology Transfer' right now. |
    # But as LaDonna a is moderator, she's able to explicitly reset a pipeline
    # even the pipeline has been locked by a different user. The reason is that
    # moderators are granted with the 'reset spain pipeline' permission.
    Given I reset the spain pipeline
    And I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"

    When I attach the file "invalid_adms.rdf" to "File"
    And I press "Upload"
    And I press "Next"

    Then I should see the following error messages:
      | error messages                                                                                                                    |
      | Spain - Center for Technology Transfer execution stopped with errors in ADMS Validation step. Please review the following errors: |
    And I should see the heading "Errors executing Spain - Center for Technology Transfer"
    And I should see "Imported data is not ADMS v2 compliant"

    Given I go to the pipeline orchestrator
    When I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"

    When I attach the file "valid_adms.rdf" to "File"
    And I press "Upload"
    And I press "Next"

    Then I should see the following success messages:
      | success messages                                                                |
      | The Spain - Center for Technology Transfer execution has finished with success. |
    And I should see the heading "Successfully executed Spain - Center for Technology Transfer import pipeline"

    # We manually delete the imported entities as they are not tracked by Behat
    # and, as a consequence, will not be automatically deleted after test. Also
    # this is a good test to check that the entities were imported and exist.
    But I delete the "Asset" solution
    And I delete the "Contact" contact information
    And I delete the "The Publisher" owner
