Feature: Solution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a solution
    Given the following collection:
      | name              | Asset distribution collection API foo                  |
      | logo              | logo.png                                               |
      | moderation        | 1                                                      |
      | closed            | 1                                                      |
      | elibrary creation | facilitators                                           |
      | uri               | http://joinup.eu/collection/asset_distribution-api-foo |
    And the following asset distribution:
      | name        | Asset distribution entity foo                      |
      | uri         | http://joinup.eu/asset_distribution/entity/api/foo |
      | description | Asset distribution sample description              |
      | file        | test.zip                                           |
    And the following solution:
      | name              | Asset distribution solution                            |
      | uri               | http://joinup.eu/asset_distribution/solution/api/foo   |
      | description       | Asset distribution sample solution                     |
      | documentation     | text.pdf                                               |
      | elibrary creation | 2                                                      |
      | landing page      | http://foo-example.com/landing                         |
      | webdav creation   | 0                                                      |
      | webdav url        | http://joinup.eu/solution/foo/webdav                   |
      | wiki              | http://example.wiki/foobar/wiki                        |
      | distribution      | http://joinup.eu/asset_distribution/entity/api/foo     |
      | groups audience   | http://joinup.eu/collection/asset_distribution-api-foo |
    Then I should have 1 solution
    And I should have 1 asset distribution
    And the "Custom title of asset distribution" asset distribution is related to the "Asset random name 2" solution

  Scenario: Programmatically create a collection using only the mandatory fields
    Given the following collection:
      | name              | Asset distribution short API bar         |
      | logo              | logo.png                                 |
      | moderation        | 1                                        |
      | closed            | 1                                        |
      | elibrary creation | facilitators                             |
      | uri               | http://joinup.eu/asset_repo/coll-api-bar |
    And the following asset distribution:
      | name        | Asset distribution entity foo short                  |
      | uri         | http://joinup.eu/asset_distribution/entity/api/short |
      | description | Asset distribution sample description                |
      | file        | test.zip                                             |
    Given the following solution:
      | name              | AD first solution mandatory short                    |
      | uri               | http://joinup.eu/asset/repositor/api/bar             |
      | description       | Another sample solution                              |
      | elibrary creation | 1                                                    |
      | distribution      | http://joinup.eu/asset_distribution/entity/api/short |
      | groups audience   | http://joinup.eu/asset_repo/coll-api-bar             |
    Then I should have 1 solution
    And I should have 1 asset distribution
    And the "Custom title of asset distribution" asset distribution is related to the "Asset random name 2" solution