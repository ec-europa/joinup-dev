@api
Feature: Export collection metadata
  As a user of Joinup I am able to retrieve the content of entities in a machine readable format.

  Scenario Outline: Export RDF data
    Given the following licence:
      | title       | Creative commons      |
      | description | This is so creative   |
      | uri         | http://i-am-creative/ |
    When I visit the homepage of the "Creative commons" licence in the <format> serialisation.
    Then the output should match the file contents of "<fixture>"
    And the content type of the response should be "<Content-Type>"

    Examples:
      | format   | Content-Type               | fixture                                               |
      | n3       | text/n3; charset=UTF-8     | content-negotiation/fierce-content-exporters.n3       |
      | rdfxml   | application/rdf+xml        | content-negotiation/fierce-content-exporters.rdfxml   |
      | ntriples | application/n-triples      | content-negotiation/fierce-content-exporters.ntriples |
      | turtle   | text/turtle; charset=UTF-8 | content-negotiation/fierce-content-exporters.ttl      |
      | jsonld   | application/ld+json        | content-negotiation/fierce-content-exporters.jsonld   |
