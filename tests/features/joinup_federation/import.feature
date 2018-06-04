@api
Feature: As a site moderator I am able to import RDF files.

  Scenario: Test the import RDF files
    Given users:
      | Username        | Roles     |
      | Antoine Batiste | moderator |
      | LaDonna         | moderator |
    And solutions:
      | uri          | title          | description         | state     |
      | http://asset | Existing asset | Initial description | validated |
    And provenance activities:
      | entity                   | enabled | author          | started          |
      | Existing asset           | yes     | Antoine Batiste | 2012-07-07 23:01 |
      | http://asset/blacklisted | no      | Antoine Batiste | 2015-12-25 01:30 |

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

    Then I should see "Spain - Center for Technology Transfer: User selection"
    And the row "Not federated asset" is checked
    And I should see the text "Not federated yet" in the "Not federated asset" row
    And the row "Asset" is checked
    And I should see the text "Federated on 07/07/2012 - 23:01 by Antoine Batiste" in the "Asset" row
    And the row "Blacklisted asset" is not checked
    And I should see the text "Blacklisted on 25/12/2015 - 01:30 by Antoine Batiste" in the "Blacklisted asset" row

    Given I press "Next"
    Then I should see the following success messages:
      | success messages                                                                |
      | The Spain - Center for Technology Transfer execution has finished with success. |
    And I should see the heading "Successfully executed Spain - Center for Technology Transfer import pipeline"

    # Check that the existing solution values were overridden.
    Given I go to the "Asset" solution edit form
    Then the "Title" field should contain "Asset"
    And the "Description" field should contain "This is an Asset."

    # Check how the provenance records were created/updated.
    Then the "Asset" entity is not blacklisted for federation
    And the "Not federated asset" entity is not blacklisted for federation
    And the "The Publisher" entity is not blacklisted for federation
    And the "Contact" entity is not blacklisted for federation
    But the "http://asset/blacklisted" entity is blacklisted for federation
    And the "http://publisher/blacklisted" entity is blacklisted for federation

    Given I visit "/admin/content/pipeline/spain/execute"
    And I attach the file "valid_adms.rdf" to "File"
    And I press "Upload"
    And I press "Next"

    Then I should see "Spain - Center for Technology Transfer: User selection"
    And the row "Not federated asset" is checked
    And the row "Asset" is checked
    And the row "Blacklisted asset" is not checked

    # Swap 'Not federated asset' with 'Blacklisted asset'.
    Given I uncheck the "Not federated asset" row
    And I check the "Blacklisted asset" row
    When I press "Next"

    # Check how the provenance records were created/updated.
    Then the "Asset" entity is not blacklisted for federation
    And the "Not federated asset" entity is blacklisted for federation
    And the "The Publisher" entity is not blacklisted for federation
    And the "Contact" entity is not blacklisted for federation
    And the "Blacklisted publisher" entity is not blacklisted for federation
    But the "Blacklisted asset" entity is not blacklisted for federation

    # We manually delete the imported entities as they are not tracked by Behat
    # and, as a consequence, will not be automatically deleted after test. Also
    # this is a good test to check that the entities were imported and exist.
    Then I delete the provenance activity of "Not federated asset" entity
    And I delete the provenance activity of "The Publisher" entity
    And I delete the provenance activity of "Contact" entity
    And I delete the provenance activity of "Blacklisted publisher" entity
    And I delete the "Contact" contact information
    And I delete the "The Publisher" owner
    And I delete the "Not federated asset" solution
    And I delete the "Blacklisted asset" solution
    And I delete the "Blacklisted publisher" owner
