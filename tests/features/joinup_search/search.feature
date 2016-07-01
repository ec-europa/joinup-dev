@api
Feature: Global search
  As a user of the site I can find content through the global search.

  Scenario: Anonymous user can find items
    Given the following solutions:
      | title          | description                                                                                                                        | spatial coverage                                             |
      | Spherification | Spherification is the culinary process of shaping a liquid into spheres                                                            | http://publications.europa.eu/resource/authority/country/EUR |
      | Foam           | The use of foam in cuisine has been used in many forms in the history of cooking:whipped cream, meringue, and mousse are all foams | http://publications.europa.eu/resource/authority/country/EUR |
    And the following collection:
      | uri        | http://joinup.eu/search_stuff |
      | title      | Molecular cooking collection  |
      | logo       | logo.png                      |
      | moderation | no                            |
      | closed     | yes                           |
      | affiliates | Spherification, Foam          |
    And news content:
      | title                 | body             | og_group_ref                   |
      | El Celler de Can Roca | The best in town | http://joinup.eu/search_stuff |

    Then I commit the solr index
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
