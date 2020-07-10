Feature: As an anonymous user, when I visit /data/dr8, I should get the cached
  EIRA vocabulary, serialized as RDF/XML.

  Scenario: Content is available and cached.
    When go to "/data/dr8"
    Then the output should match the file contents of "../../../resources/fixtures/EIRA_SKOS.rdf"
    # Check if the page is cached.
    When I reload the page
    Then the page should be cached
    And the output should match the file contents of "../../../resources/fixtures/EIRA_SKOS.rdf"
