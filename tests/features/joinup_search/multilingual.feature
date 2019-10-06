@api @terms
Feature: Multilingual search
  As a user of the site I should only see English content

  Scenario: Anonymous user sees English version only
    Given the language "ca" is enabled
    And the following collection:
      | title            | Molecular cooking collection |
      | logo             | logo.png                     |
      | moderation       | no                           |
      | policy domain    | Demography                   |
      | spatial coverage | Belgium                      |
      | state            | validated                    |
    And the multilingual "El celler de Can Roca" solution of "Molecular cooking collection" collection
    When I go to the "Molecular cooking collection" collection
    Then I should see 1 tile
