@api
Feature: Global search
  As a user of the site I can find content through the global search.

  Scenario: Anonymous user can find items
    Given the following solutions:
      | title          | description                                                                                                                        | state     |
      | Spherification | Spherification is the culinary process of shaping a liquid into spheres                                                            | validated |
      | Foam           | The use of foam in cuisine has been used in many forms in the history of cooking:whipped cream, meringue, and mousse are all foams | validated |
      # Taxonomies are not yet implemented, so uncomment this after #ISAICP-2545 is done
      # | spatial coverage | http://publications.europa.eu/resource/authority/country/EUR            |
    And the following collection:
      | title      | Molecular cooking collection |
      | logo       | logo.png                     |
      | moderation | no                           |
      | affiliates | Spherification, Foam         |
      | state      | validated                    |
    And news content:
      | title                 | body             | collection                   | state     |
      | El Celler de Can Roca | The best in town | Molecular cooking collection | validated |

    Given I am logged in as a user with the "authenticated" role
    When I am at "/search"
    # All content visible
    Then I should see the text "Molecular cooking collection"
    Then I should see the text "El Celler de Can Roca"
    Then I should see the text "Spherification"
    Then I should see the text "Foam"

    # Select link in the 'type' facet.
    Then I click "solution" in the "Left sidebar" region
    # @todo Re-enable this check when the tile view mode created.
    # (The default view mode of solutions holds a link to it's collection)
    # Then I should not see the text "Molecular cooking collection"
    Then I should not see the text "El Celler de Can Roca"
    Then I should see the text "Spherification"
    Then I should see the text "Foam"
