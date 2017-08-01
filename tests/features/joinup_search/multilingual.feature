@api @terms
Feature: Multilingual search
  As a user of the site I should only see English content

  Scenario: Anonymous user sees English version only
    Given the language "ca" is enabled
    Given the multilingual "El celler de Can Roca" solution
    And the following collection:
      | title            | Molecular cooking collection |
      | logo             | logo.png                     |
      | moderation       | no                           |
      | affiliates       | El celler de Can Roca        |
      | policy domain    | Demography                   |
      | spatial coverage | Belgium                      |
      | state            | validated                    |
    When I go to the "Molecular cooking collection" collection
    Then I should see 2 tiles
