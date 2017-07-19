@api
Feature:
  As an owner of a website
  In order to guarantee data persistence
  All semantic assets need to have a persistent URI

  Scenario: Entities should have distinct URI pattern
    Given the following solution:
      | title       | Persistent solution |
      | description | Persistent solution |
      | state       | validated           |
    And the following collection:
      | title      | Persistent collection |
      | logo       | logo.png              |
      | affiliates | Persistent solution   |
      | state      | validated             |
    And the following release:
      | title          | Persistent release  |
      | release number | 23                  |
      | description    | Persistent release. |
      | is version of  | Persistent solution |
      | state          | validated           |
    And the following distribution:
      | title       | Persistent distribution |
      | description | Persistent distribution |
      | access url  | test.zip                |
      | solution    | Persistent solution     |
    And the following licence:
      | title       | Persistent licence |
      | description | Persistent licence |
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
