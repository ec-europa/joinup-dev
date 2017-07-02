@api
Feature:
  As an owner of a website
  In order to provide the visitors with user friendly urls
  I need to have url aliases generated automatically.

  Scenario: Entities should have distinct pathauto aliases.
    Given the following solution:
      | title       | Pathauto solution |
      | description | Pathauto solution |
      | state       | validated         |
    And the following collection:
      | title      | Pathauto collection |
      | logo       | logo.png            |
      | affiliates | Pathauto solution   |
      | state      | validated           |
    And the following release:
      | title          | Pathauto release  |
      | release number | 23                |
      | description    | Pathauto release. |
      | is version of  | Pathauto solution |
      | state          | validated         |
    And the following licence:
      | title       | Pathauto licence |
      | description | Pathauto licence |
    And discussion content:
      | title               | body                | collection          | state     |
      | Pathauto discussion | Pathauto discussion | Pathauto collection | validated |
    And document content:
      | title             | body              | collection          | state     |
      | Pathauto document | Pathauto document | Pathauto collection | validated |
    And event content:
      | title          | body           | collection          | state     |
      | Pathauto event | Pathauto event | Pathauto collection | validated |
    And news content:
      | title         | body          | collection          | state     |
      | Pathauto news | Pathauto news | Pathauto collection | validated |

    When I go to the "Pathauto collection" collection
    Then the url should match "collection/pathauto-collection"
    When I go to the "Pathauto solution" solution
    Then the url should match "solution/pathauto-solution"
    When I go to the "Pathauto release" release
    Then the url should match "release/pathauto-release/23"
    When I visit the "Pathauto document" document
    Then the url should match "document/pathauto-document"
    When I visit the "Pathauto discussion" discussion
    Then the url should match "discussion/pathauto-discussion"
    When I visit the "Pathauto event" event
    Then the url should match "event/pathauto-event"
    When I visit the "Pathauto news" news
    Then the url should match "news/pathauto-news"