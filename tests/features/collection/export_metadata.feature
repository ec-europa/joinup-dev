@api @group-d
Feature: Export collection metadata
  As a user of Joinup I should be able to download the ADMS properties of the collections.

  Scenario: Export RDF data
    Given the following collection:
      | title | Fierce federation of content exporters |
      | state | validated                              |
    When I visit the homepage of the "Fierce federation of content exporters" collection
    Then I click "Metadata"
    Then I should see the link "Turtle Terse RDF Triple Language"
    And I should see the link "RDF/XML"

