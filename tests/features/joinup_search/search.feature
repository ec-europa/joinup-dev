@api
Feature: Global search
  As a user of the site I can find content through the global search.

  Scenario: Anonymous user can find items
    Given the following collection:
      | title      | Molecular cooking collection |
      | logo       | logo.png                     |
      | moderation | no                           |
      | closed     | yes                          |
    And news content:
      | title                 | body             | collection                   |
      | El Celler de Can Roca | The best in town | Molecular cooking collection |
    And the following solution:
      | title            | Spherification                                                          |
      | description      | Spherification is the culinary process of shaping a liquid into spheres |
      | collection       | Molecular cooking collection                                            |
      | spacial coverage | http://publications.europa.eu/resource/authority/country/EUR            |
    And the following solution:
      | title            | Foam                                                                                                                               |
      | description      | The use of foam in cuisine has been used in many forms in the history of cooking:whipped cream, meringue, and mousse are all foams |
      | collection       | Molecular cooking collection                                                                                                       |
      | spacial coverage | http://publications.europa.eu/resource/authority/country/EUR                                                                       |
    And all content is indexed
    Given I am logged in as a user with the "authenticated" role
    When I am at "/search"
    # All content visible
    Then I should see the text "Molecular cooking collection"
    Then I should see the text "El Celler de Can Roca"
    Then I should see the text "Spherification"
    Then I should see the text "Foam"
    # Select link in the spacial coverage facet.
    Then I click "European Union" in the "Right sidebar" region
    Then I should not see the text "Molecular cooking collection"
    Then I should not see the text "El Celler de Can Roca"
    Then I should see the text "Spherification"
    Then I should see the text "Foam"
