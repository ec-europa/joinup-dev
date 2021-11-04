@api @group-c
Feature: As a site moderator I am able to import RDF files.

  Background:
    Given collection:
      | uri   | http://administracionelectronica.gob.es/ctt |
      | title | Spain                                       |
      | state | validated                                   |
    And users:
      | Username        | Roles     |
      | Antoine Batiste | moderator |
    And no pipelines have run
    And I am logged in as "Antoine Batiste"

  Scenario: Test available pipelines
    Given I click "ADMS-AP importer" in the "Administration toolbar" region
    Then the "Data pipeline" select should contain the following options:
      | - Select -                               |
      | Danish Public Sector Interoperability    |
      | EU Schemantic Interoperability Catalogue |
      | Joinup collection                        |
      | Slovenian Interoperability Portal - NIO  |
      | Spain - Center for Technology Transfer   |

  Scenario: Test the pipeline functionality
    Given users:
      | Username         | Roles     |
      | LaDonna          | moderator |
      | Janette Desautel |           |

    And I click "ADMS-AP importer" in the "Administration toolbar" region
    And I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"
    Then I should see "Spain - Center for Technology Transfer: Manual upload"

    # Go back to the pipeline selection. You should be redirected to the current
    # active/unfinished step.
    Given I click "ADMS-AP importer" in the "Administration toolbar" region
    Then I should see "Spain - Center for Technology Transfer: Manual upload"

    # Test the wizard reset.
    Given I click "Cancel"
    Then I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"

    # The pipeline should be locked for different user.
    Given I am logged in as LaDonna
    Given I click "ADMS-AP importer" in the "Administration toolbar" region
    And I select "Spain - Center for Technology Transfer" from "Data pipeline"
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

    # A regular user cannot reset the pipeline.
    Given I am logged in as "Janette Desautel"
    When I go to "/admin/content/pipeline/spain/reset"
    Then the response status code should be 403

    Given I am logged in as LaDonna
    When I go to "/admin/content/pipeline/spain/reset"
    Then the response status code should be 200

  Scenario: Test the import of a file that doesn't pass the ADMS-AP validation.
    Given I click "ADMS-AP importer" in the "Administration toolbar" region
    And I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"

    When I attach the file "invalid_adms.rdf" to "File"
    And I press "Upload"

    When I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see the following error messages:
      | error messages                                                                                                                    |
      | Spain - Center for Technology Transfer execution stopped with errors in ADMS Validation step. Please review the following errors: |
    And I should see the heading "Errors executing Spain - Center for Technology Transfer"
    And I should see "Imported data is not ADMS v2 compliant"

  Scenario: Test the import of a file that doesn't pass the Drupal validation.
    Given I click "ADMS-AP importer" in the "Administration toolbar" region
    And I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"
    When I attach the file "invalid_drupal.rdf" to "File"

    And I press "Upload"

    When I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see the heading "Spain - Center for Technology Transfer: User selection"
    And the row "Solution 1 [http://example.com/solution/1]" is selected
    And the row "<missing label> [http://example.com/solution/2]" is selected

    When I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see the following error message:
      | error messages                                                                                                                                 |
      | Spain - Center for Technology Transfer execution stopped with errors in Joinup compliance validation step. Please review the following errors: |
    And I should see the heading "Errors executing Spain - Center for Technology Transfer"
    And I should see the following lines of text:
      | A solution titled Solution 1 already exists in this collection.                        |
      | The referenced entity (rdf_entity: http://example.com/owner/invalid) does not exist.   |
      | The referenced entity (rdf_entity: http://example.com/contact/invalid) does not exist. |
      | This value should not be null.                                                         |
      | The distribution Windows is linked also by the Asset release 1 release.                |

  @terms
  Scenario: Test a successful import.
    Given user:
      | Username    | CS Owner                |
      | First name  | Collection and solution |
      | Family name | Owner                   |
      | E-mail      | csowner@example.com     |
    And collection:
      | uri   | http://nio.gov.si/nio/ |
      | title | NIO                    |
      | state | validated              |
    And solutions:
      | uri                           | title                       | collection | description         | state     | modification date |
      | http://example.com/solution/2 | Local version of Solution 2 | NIO        | Initial description | validated | 15-07-2018        |
    And the following solution user membership:
      | solution                    | user     | roles                      | state  |
      | Local version of Solution 2 | CS Owner | facilitator, administrator | active |
    And the following distribution:
      | uri               | http://example.com/distribution/3          |
      | title             | Local version of a standalone distribution |
      | description       | No one cares.                              |
      | access url        | http://example.com/file.zip                |
      | parent            | Local version of Solution 2                |
      | creation date     | 15-07-2018                                 |
      | modification date | 15-07-2018                                 |
    And the following collection user membership:
      | collection | user     | roles                      | state  |
      | NIO        | CS Owner | facilitator, administrator | active |
    # Hash for solution 2 includes the title 'Local version of solution 2' so
    # that it will detect changes in the first attempt.
    # Hash for solution 3 is the one matching to the values from valid_adms.rdf.
    And provenance activities:
      | entity                        | enabled | associated with | author          | started          | hash                             |
      | Local version of Solution 2   | yes     | NIO             | Antoine Batiste | 2012-07-07 23:01 | 958c1834800217af62253353d0d4bdef |
      | http://example.com/solution/3 | no      | NIO             | Antoine Batiste | 2015-12-25 01:30 | e5e7aceb15c50ab628c744673daf5ca1 |
    Given SPDX licences:
      | uri                                 | title       |
      | http://spdx.org/licenses/Apache-2.0 | Apache 2    |
      | http://spdx.org/licenses/MIT        | MIT License |
    # The licence contained in valid_adms.rdf is named "A federated licence".
    # However, the goal is to not import or update any values in the licence
    # entity so the following licences have different details.
    And the following licences:
      | uri                          | title                    | description               | type          | spdx licence |
      | http://example.com/licence/1 | Licence same as Apache 2 | Licence agreement details | Public domain | Apache 2     |
      | http://example.com/licence/2 | Licence same as MIT      | So on...                  | Public domain | MIT License  |

    # Initially the pipeline report should show that the pipelines have not run.
    When I go to "/admin/reporting"
    And I click "Pipeline report"
    Then the "pipeline log" table should contain the following columns:
      | Pipeline                                 | Last executed |
      | Danish Public Sector Interoperability    | Never         |
      | EU Schemantic Interoperability Catalogue | Never         |
      | Joinup collection                        | Never         |
      | Slovenian Interoperability Portal - NIO  | Never         |
      | Spain - Center for Technology Transfer   | Never         |

    # Start the import.
    Given I click "ADMS-AP importer" in the "Administration toolbar" region
    And I select "Slovenian Interoperability Portal - NIO" from "Data pipeline"
    And I press "Execute"
    When I attach the file "valid_adms.rdf" to "File"
    And I press "Upload"

    When I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see "Slovenian Interoperability Portal - NIO: User selection"
    # Solution 1 is checked because it is new.
    And the row "Solution 1 [http://example.com/solution/1?query=4#]" is checked
    # Regression test to ensure that releases are not confused for solutions.
    And I should not see the text "Asset release 1"
    And I should not see the text "Asset release 2"
    And I should see the text "Not federated yet" in the "Solution 1 [http://example.com/solution/1?query=4#]" row
    # Solution 2 is checked because there are changes detected (The title is different).
    And the row "Solution 2 [http://example.com/solution/2]" is checked
    And I should see the text "Federated on 07/07/2012 - 23:01 by Antoine Batiste" in the "Solution 2 [http://example.com/solution/2]" row
    # Solution 3 is not checked because it is marked as blacklisted.
    And the row "Solution 3 [http://example.com/solution/3]" is not checked
    And I should see the text "Blacklisted on 25/12/2015 - 01:30 by Antoine Batiste" in the "Solution 3 [http://example.com/solution/3]" row

    Given I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see the following success messages:
      | success messages                                                             |
      | Slovenian Interoperability Portal - NIO execution has finished with success. |
    And I should see the heading "Successfully executed Slovenian Interoperability Portal - NIO import pipeline"

    # Check how the provenance records were created/updated.
    Then the "Solution 1" entity is not blacklisted for federation
    And the "Solution 2" entity is not blacklisted for federation
    And the "Asset release 1" entity is not blacklisted for federation
    And the "Asset release 2" entity is not blacklisted for federation
    And the "Windows" entity is not blacklisted for federation
    And the "Linux" entity is not blacklisted for federation
    And the "A standalone distribution" entity is not blacklisted for federation
    And the "The Publisher" entity is not blacklisted for federation
    And the "A local authority" entity is not blacklisted for federation
    And the "Contact" entity is not blacklisted for federation
    But the "http://example.com/solution/3" entity is blacklisted for federation
    And the "http://example.com/distribution/4" entity is blacklisted for federation

    # Licence should be excluded from the import process.
    And the "Licence same as Apache 2" entity should not have a related provenance activity
    # Regression test to assert that created entities have a creation time.
    And the "Solution 1" solution has a creation date set

    # Regression test to ensure that solutions with complex urls still receive all menu items.
    # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5608
    When I visit the "Solution 1" solution
    Then I should see the following group menu items in the specified order:
      | text     |
      | Overview |
      | Members  |
      | About    |

    # Check the affiliation of federated solutions.
    But the "Solution 1" solution should be affiliated with the "NIO" collection
    And the "Solution 2" solution should be affiliated with the "NIO" collection

    # Check that the existing solution values were overridden.
    Given I go to the edit form of the "Solution 2" solution
    Then the "Title" field should contain "Solution 2"
    And the "Description" field should contain "This solution has a standalone distribution."

    # Verify the user can edit non federated values.
    And I select "E-health" from "Topic"
    And I press "Publish"
    Then I should see the heading "Solution 2"

    # Verify that the collection owner can edit the new solutions.
    When I am logged in as "CS Owner"
    Given I go to the edit form of the "Solution 1" solution
    Then the response status code should be 200

    # Ensure that the og relation is set between the distribution and the solution.
    When I go to the "Windows" asset distribution
    Then I should see the heading "Solution 1"

    # And the SPDX licence IDs were converted to Joinup licence IDs.
    And I should see "Licence same as Apache 2"
    When I go to the "Linux" asset distribution
    And I should see "Licence same as Apache 2"
    When I go to the "A standalone distribution" asset distribution
    And I should see "Licence same as MIT"

    # Re-import.
    Given I am logged in as "Antoine Batiste"
    And I click "ADMS-AP importer" in the "Administration toolbar" region
    And I select "Slovenian Interoperability Portal - NIO" from "Data pipeline"
    And I press "Execute"
    And I attach the file "valid_adms.rdf" to "File"
    And I press "Upload"

    When I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see "Slovenian Interoperability Portal - NIO: User selection"
    # Solution 1 is unchecked because it is unchanged.
    And the row "Solution 1" is not checked
    # Solution 2 is unchecked because it is unchanged.
    And the row "Solution 2" is not checked
    # Solution 3 is unchecked because it is blacklisted.
    And the row "Solution 3" is not checked

    Given I check the "Solution 3" row
    When I press "Next"
    And I wait for the pipeline batch job to finish

    # Check how the provenance records were updated.
    Then I should see the heading "Successfully executed Slovenian Interoperability Portal - NIO import pipeline"
    And the "Solution 2" entity is not blacklisted for federation
    And the "Solution 3" entity is not blacklisted for federation
    And the "MacOS" entity is not blacklisted for federation
    And the "A standalone distribution" entity is not blacklisted for federation
    And the "The Publisher" entity is not blacklisted for federation
    And the "A local authority" entity is not blacklisted for federation
    And the "Contact" entity is not blacklisted for federation

    # Licences should still be excluded from the import process.
    And the "Licence same as Apache 2" entity should not have a related provenance activity

    # Unchanged solutions and dependencies do not become blacklisted if they are not federated.
    And the "Solution 1" entity is not blacklisted for federation
    And the "Asset release 1" entity is not blacklisted for federation
    And the "Asset release 2" entity is not blacklisted for federation
    And the "Windows" entity is not blacklisted for federation
    And the "Linux" entity is not blacklisted for federation

    # Check the affiliation of federated solutions.
    And the "Solution 1" solution should be affiliated with the "NIO" collection
    And the "Solution 2" solution should be affiliated with the "NIO" collection
    And the "Solution 3" solution should be affiliated with the "NIO" collection

    # Check that the Topic value was not overridden.
    Given I go to the edit form of the "Solution 2" solution
    # The text is '-E-health' because of the '-' prepended to children options.
    And the option with text "-E-health" from select "Topic" is selected
    And I press "Publish"
    Then I should see the heading "Solution 2"

    # Check that provenance activity records are not indexed.
    When I am at "/search"
    Then I should not see the following facet items "Activities"

    # Check that the pipeline report shows that the pipeline has run.
    When I go to "/admin/reporting"
    And I click "Pipeline report"
    Then the "pipeline log" table should contain the following columns:
      | Pipeline                                 | Last executed |
      | Danish Public Sector Interoperability    | Never         |
      | EU Schemantic Interoperability Catalogue | Never         |
      | Joinup collection                        | Never         |
      | Slovenian Interoperability Portal - NIO  | 0 days ago    |
      | Spain - Center for Technology Transfer   | Never         |


    # We manually delete the imported entities as they are not tracked by Behat
    # and, as a consequence, will not be automatically deleted after test. Also
    # this is a good test to check that the entities were imported and exist.
    And I delete the provenance activity of "http://example.com/solution/1?query=4#" entity
    And I delete the provenance activity of "http://example.com/solution/2" entity
    And I delete the provenance activity of "http://example.com/solution/3" entity
    And I delete the provenance activity of "http://example.com/release/1" entity
    And I delete the provenance activity of "http://example.com/release/2" entity
    And I delete the provenance activity of "http://example.com/distribution/1" entity
    And I delete the provenance activity of "http://example.com/distribution/2" entity
    And I delete the provenance activity of "http://example.com/distribution/3" entity
    And I delete the provenance activity of "http://example.com/distribution/4" entity
    And I delete the provenance activity of "http://example.com/owner/1" entity
    And I delete the provenance activity of "http://example.com/owner/2" entity
    And I delete the provenance activity of "http://example.com/contact/1" entity
    And I delete the "Contact" contact information
    And I delete the "The Publisher" owner
    And I delete the "A local authority" owner
    And I delete the "Asset release 1" release
    And I delete the "Asset release 2" release
    And I delete the "Windows" asset distribution
    And I delete the "Linux" asset distribution
    And I delete the "A standalone distribution" asset distribution
    And I delete the "MacOS" asset distribution
    And I delete the "Solution 1" solution
    # Solution 2 is deleted automatically by Behat.
    And I delete the "Solution 3" solution

  @joinup_collection
  Scenario: Test that solutions cannot be re-federated in a different collection.
    Given I click "ADMS-AP importer" in the "Administration toolbar" region
    And I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"
    When I attach the file "single_solution_valid_adms.rdf" to "File"
    And I press "Upload"

    When I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see "Spain - Center for Technology Transfer: User selection"
    And the row "Single solution [http://example.com/solution/single]" is checked

    Given I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see the following success messages:
      | success messages                                                                |
      | The Spain - Center for Technology Transfer execution has finished with success. |
    And I should see the heading "Successfully executed Spain - Center for Technology Transfer import pipeline"

    # Try to federate in a different collection.
    Given I visit "/admin/content/pipeline/joinup_collection/execute"
    And I attach the file "single_solution_valid_adms.rdf" to "File"
    And I press "Upload"

    When I press "Next"
    And I wait for the pipeline batch job to finish

    Then I should see "Joinup collection: User selection"
    # The url of the entity is not included as the alias with the base url is included.
    And I should see the text "Federation record exists with "

    # The federation was incomplete. Reset the pipeline to conclude the test.
    Then I visit "/admin/content/pipeline/joinup_collection/reset"
    # We manually delete the imported entities as they are not tracked by Behat
    # and, as a consequence, will not be automatically deleted after test. Also
    # this is a good test to check that the entities were imported and exist.
    And I delete the provenance activity of "http://example.com/solution/single" entity
    And I delete the provenance activity of "http://example.com/owner/single" entity
    And I delete the provenance activity of "http://example.com/contact/single" entity
    And I delete the "Contact" contact information
    And I delete the "The Publisher" owner
    And I delete the "Single solution" solution
