@api @group-g
Feature: As a user with 'RDF graph manager' role I want to be able to upload RDF
  files in custom graphs and make them available as triples on Joinup RDF endpoint.

  Scenario: Test RDF file upload.

    Given I am logged in as an "RDF graph manager"
    When I click "RDF graphs"
    Then I should see the heading "RDF graphs"
    And I should see "There are no RDF graph items yet."

    When I click "Add RDF graph"
    Then I should see the heading "Add RDF Graph"

    # Reserved word as title.
    When I fill in "Title" with "Add"
    And I press "Save"
    Then I should see the following error messages:
      | error messages                                                                       |
      | Cannot use 'Add' as title, as it's a reserved word. Please choose a different title. |
      | RDF file field is required.                                                          |

    When I fill in "Title" with "Arbitrary graph"
    And I attach the file "file.ttl" to "RDF file"
    And I press "Save"
    Then I should see the success message "The RDF graph Arbitrary graph triples were imported in the Joinup triplestore. However, it might take up to 24 hours until the changes will be queryable at our SPARQL public endpoint."
    And I should see the heading "Arbitrary graph"
    And I should see the link "file.ttl"
    And the "Arbitrary graph" RDF graph contains triples:
      | subject                         | predicate                                       | object                                      |
      | http://joinup.eu/whatever       | http://www.w3.org/1999/02/22-rdf-syntax-ns#type | http://www.w3.org/2004/02/skos/core#Concept |
      | http://joinup.eu/whatever       | http://www.w3.org/2004/02/skos/core#inScheme    | http://joinup.eu/type                       |
      | http://joinup.eu/whatever       | http://www.w3.org/2004/02/skos/core#prefLabel   | Air cylinders                               |
      | http://joinup.eu/something-else | http://www.w3.org/1999/02/22-rdf-syntax-ns#type | http://www.w3.org/2004/02/skos/core#Concept |
      | http://joinup.eu/something-else | http://www.w3.org/2004/02/skos/core#inScheme    | http://joinup.eu/different-type             |
      | http://joinup.eu/something-else | http://www.w3.org/2004/02/skos/core#prefLabel   | Oil cylinders                               |

    # Replace the file.
    When I click "RDF graphs"
    And I click "Edit" in the "Arbitrary graph" row
    And I press "Remove"
    And I attach the file "file2.ttl" to "RDF file"
    And I press "Save"
    Then I should see the success message "The RDF graph Arbitrary graph triples were imported in the Joinup triplestore. However, it might take up to 24 hours until the changes will be queryable at our SPARQL public endpoint."
    And the "Arbitrary graph" RDF graph contains triples:
      | subject                            | predicate                                       | object                                      |
      | http://joinup.eu/totally-different | http://www.w3.org/1999/02/22-rdf-syntax-ns#type | http://www.w3.org/2004/02/skos/core#Concept |
      | http://joinup.eu/totally-different | http://www.w3.org/2004/02/skos/core#inScheme    | http://joinup.eu/types                      |
      | http://joinup.eu/totally-different | http://www.w3.org/2004/02/skos/core#prefLabel   | Hidro cylinders                             |

    # Title duplication.
    When I click "RDF graphs"
    And I click "Add RDF graph"
    When I fill in "Title" with "Arbitrary graph"
    And I press "Save"
    Then I should see the error message "An RDF graph titled 'Arbitrary graph' already exists. Please choose a different title."

    # Deletion.
    When I click "RDF graphs"
    And I click "Delete" in the "Arbitrary graph" row
    Then I should see the heading "Are you sure you want to delete RDF graph Arbitrary graph?"
    When I press "Delete"
    Then I should see the heading "RDF graphs"
    And I should not see the link "Arbitrary graph"
    And triples created during the test were deleted
