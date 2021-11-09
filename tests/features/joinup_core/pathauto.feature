@api @group-c
Feature:
  As an owner of a website
  In order to provide the visitors with user friendly urls
  I need to have url aliases generated automatically.

  Scenario: Entities should have distinct pathauto aliases.
    Given the following collection:
      | title | Pathauto collection |
      | logo  | logo.png            |
      | state | validated           |
    And the following solution:
      | title       | Pathauto solution   |
      | description | Pathauto solution   |
      | state       | validated           |
      | collection  | Pathauto collection |
    And the following release:
      | title          | Pathauto release  |
      | release number | 23                |
      | description    | Pathauto release. |
      | is version of  | Pathauto solution |
      | state          | validated         |
    And the following distribution:
      | title       | Pathauto distribution  |
      | description | Pathauto distribution. |
      | parent      | Pathauto solution      |
    And the following licence:
      | title       | Pathauto licence |
      | description | Pathauto licence |
    And discussion content:
      | title                 | body                  | collection          | solution          | state     |
      | Pathauto discussion   | Pathauto discussion   | Pathauto collection |                   | validated |
      | Pathauto discussion 2 | Pathauto discussion 2 |                     | Pathauto solution | validated |
    And document content:
      | title               | body                | collection          | solution          | state     |
      | Pathauto document   | Pathauto document   | Pathauto collection |                   | validated |
      | Pathauto document 2 | Pathauto document 2 |                     | Pathauto solution | validated |
    And event content:
      | title            | body             | collection          | solution          | state     |
      | Pathauto event   | Pathauto event   | Pathauto collection |                   | validated |
      | Pathauto event 2 | Pathauto event 2 |                     | Pathauto solution | validated |
    And news content:
      | title           | body            | collection          | solution          | state     |
      | Pathauto news   | Pathauto news   | Pathauto collection |                   | validated |
      | Pathauto news 2 | Pathauto news 2 |                     | Pathauto solution | validated |
    And custom_page content:
      | title                    | body          | collection          | solution          | state     |
      | Pathauto collection page | Pathauto page | Pathauto collection |                   | validated |
      | Pathauto solution page   | Pathauto page |                     | Pathauto solution | validated |

    When I go to the "Pathauto collection" collection
    Then the url should match "collection/pathauto-collection"
    When I go to the "Pathauto solution" solution
    Then the url should match "collection/pathauto-collection/solution/pathauto-solution"
    When I go to the "Pathauto release" release
    Then the url should match "collection/pathauto-collection/solution/pathauto-solution/release/23"
    When I go to the "Pathauto distribution" distribution
    Then the url should match "collection/pathauto-collection/solution/pathauto-solution/distribution/pathauto-distribution"
    When I visit the "Pathauto document" document
    Then the url should match "collection/pathauto-collection/document/pathauto-document"
    When I visit the "Pathauto document 2" document
    Then the url should match "collection/pathauto-collection/solution/pathauto-solution/document/pathauto-document-2"
    When I visit the "Pathauto discussion" discussion
    Then the url should match "collection/pathauto-collection/discussion/pathauto-discussion"
    When I visit the "Pathauto discussion 2" discussion
    Then the url should match "collection/pathauto-collection/solution/pathauto-solution/discussion/pathauto-discussion-2"
    When I visit the "Pathauto event" event
    Then the url should match "collection/pathauto-collection/event/pathauto-event"
    When I visit the "Pathauto event 2" event
    Then the url should match "collection/pathauto-collection/solution/pathauto-solution/event/pathauto-event-2"
    When I visit the "Pathauto news" news
    Then the url should match "collection/pathauto-collection/news/pathauto-news"
    When I visit the "Pathauto news 2" news
    Then the url should match "collection/pathauto-collection/solution/pathauto-solution/news/pathauto-news-2"
    When I visit the "Pathauto collection page" custom page
    Then the url should match "collection/pathauto-collection/pathauto-collection-page"
    When I visit the "Pathauto solution page" custom page
    Then the url should match "collection/pathauto-collection/solution/pathauto-solution/pathauto-solution-page"
