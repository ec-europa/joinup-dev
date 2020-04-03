@api @terms
Feature: SEO for RDF entities.
  As an owner of the website
  in order for my RDF entities to be better visible on the web
  I need proper meta information to be encapsulated in the html code.

  Scenario Outline: Basic JSON meta information for collections and solutions.
    Given the following <type>:
  | title | SEO entity |
  | state | validated  |

    When I visit the "SEO entity" <type>
    Then the rdf metadata of the "SEO entity" rdf entity should be attached in the page

    When I click "<link>"
    Then the rdf metadata of the "SEO entity" rdf entity should not be attached in the page

    Examples:
      | type       | link        |
      | collection | Collections |
      | solution   | Solutions   |

  Scenario: Basic JSON meta information for releases and distributions.
    Given the following solution:
      | title | SEO solution |
      | state | validated    |
    And the following release:
      | title         | SEO release  |
      | is version of | SEO solution |
      | state         | validated    |
    And the following distribution:
      | title  | SEO distribution |
      | parent | SEO release      |

    When I go to the "SEO release" release
    Then the rdf metadata of the "SEO release" rdf entity should be attached in the page

    When I go to the "SEO distribution" distribution
    Then the rdf metadata of the "SEO distribution" rdf entity should be attached in the page

  Scenario: Basic JSON meta information for licences.
    Given the following licence:
      | title       | SEO licence            |
      | description | Licence to perform SEO |
      | type        | Public domain          |
    When I go to the "SEO licence" licence
    Then the rdf metadata of the "SEO licence" rdf entity should be attached in the page
