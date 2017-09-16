@api
Feature:
  As an owner of a website
  In order to guarantee data persistence
  All semantic assets need to have a persistent URI

  Scenario: Entities should have distinct URI pattern
    Given the following solution:
      | title       | Persistent solution                                            |
      | description | Persistent solution                                            |
      | state       | validated                                                      |
      | uri         | http://data.europa.eu/w21/ffb0ffc9-7704-45d3-95b3-42706b6320e5 |
    And the following collection:
      | title      | Persistent collection                                          |
      | logo       | logo.png                                                       |
      | affiliates | Persistent solution                                            |
      | state      | validated                                                      |
      | uri        | http://data.europa.eu/w21/37b8103e-26e5-4c81-8ce5-43ced02ff7d0 |
    And the following release:
      | title          | Persistent release                                             |
      | release number | 23                                                             |
      | description    | Persistent release.                                            |
      | is version of  | Persistent solution                                            |
      | state          | validated                                                      |
      | uri            | http://data.europa.eu/w21/98004ec9-62f3-4734-a1b6-af7e4838b09c |
    And the following distribution:
      | title       | Persistent distribution                                        |
      | description | Persistent distribution                                        |
      | access url  | test.zip                                                       |
      | solution    | Persistent solution                                            |
      | uri         | http://data.europa.eu/w21/643a2a52-da3b-4594-92bb-295d8134e1fb |
    And the following licence:
      | title       | Persistent licence                                             |
      | description | Persistent licence                                             |
      | uri         | http://data.europa.eu/w21/4205229d-92b6-4cac-80af-d8c2296d923c |
    And discussion content:
      | title                 | body                  | collection            | state     |
      | Persistent discussion | Persistent discussion | Persistent collection | validated |
    And document content:
      | title               | body                | collection            | state     |
      | Persistent document | Persistent document | Persistent collection | validated |
    And event content:
      | title            | body             | collection            | state     |
      | Persistent event | Persistent event | Persistent collection | validated |
    And news content:
      | title           | body            | collection            | state     |
      | Persistent news | Persistent news | Persistent collection | validated |
    And custom_page content:
      | title           | body            | collection            | state     |
      | Persistent page | Persistent page | Persistent collection | validated |

    When I go to the "Persistent collection" collection
    And I click "About"
    Then the persistent url should contain "http://data.europa.eu/w21"

    When I go to the "Persistent solution" solution
    And I click "About"
    Then the persistent url should contain "http://data.europa.eu/w21"

    When I go to the "Persistent release" release
    Then the persistent url should contain "http://data.europa.eu/w21"

    When I go to the "Persistent distribution" asset distribution
    Then the persistent url should contain "http://data.europa.eu/w21"

    When I go to the "Persistent licence" licence
    Then the persistent url should contain "http://data.europa.eu/w21"

    When I visit the "Persistent document" document
    Then the persistent url should contain "/node/"

    When I visit the "Persistent discussion" discussion
    Then the persistent url should contain "/node/"

    When I visit the "Persistent event" event
    Then the persistent url should contain "/node/"

    When I visit the "Persistent news" news
    Then the persistent url should contain "/node/"

    When I visit the "Persistent page" custom page
    Then the persistent url should contain "/node/"

    # Our semantic content has a persistent canonical path at Europe's
    # official data portal: "http://data.europa.eu/w21/{uuid}". The data portal
    # links back to the Joinup servers. In order to resolve the UUIDs we employ
    # the intermediate path '/data/{uuid}' which will forward the request to the
    # actual page that contains the content in human digestible format.
    Given I am on "data/ffb0ffc9-7704-45d3-95b3-42706b6320e5"
    Then I should see the heading "Persistent solution"

    Given I am on "data/37b8103e-26e5-4c81-8ce5-43ced02ff7d0"
    Then I should see the heading "Persistent collection"

    Given I am on "data/98004ec9-62f3-4734-a1b6-af7e4838b09c"
    Then I should see the heading "Persistent release 23"

    Given I am on "data/643a2a52-da3b-4594-92bb-295d8134e1fb"
    Then I should see the heading "Persistent distribution"

    Given I am on "data/4205229d-92b6-4cac-80af-d8c2296d923c"
    Then I should see the heading "Persistent licence"
