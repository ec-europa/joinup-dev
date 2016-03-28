Feature: Solution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a solution
    Given the following collection:
      | name              | Solution API foo                             |
      | logo              | logo.png                                     |
      | moderation        | 1                                            |
      | closed            | 1                                            |
      | elibrary creation | facilitators                                 |
      | uri               | http://joinup.eu/collection/solution-api-foo |
    And the following solution:
     | name              | My first solution                            |
     | uri               | http://joinup.eu/solution/api/foo            |
     | description       | A sample solution                            |
     | documentation     | text.pdf                                     |
     | elibrary creation | 2                                            |
     | landing page      | http://foo-example.com/landing               |
     | webdav creation   | 0                                            |
     | webdav url        | http://joinup.eu/solution/foo/webdav         |
     | wiki              | http://example.wiki/foobar/wiki              |
     | groups audience   | http://joinup.eu/collection/solution-api-foo |
    Then I should have 1 solution

  Scenario: Programmatically create a collection using only the mandatory fields
    Given the following collection:
      | name              | Solution API bar                             |
      | logo              | logo.png                                     |
      | moderation        | 1                                            |
      | closed            | 1                                            |
      | elibrary creation | facilitators                                 |
      | uri               | http://joinup.eu/collection/solution-api-bar |
    Given the following solution:
      | name              | My first solution mandatory                  |
      | uri               | http://joinup.eu/solution/api/bar            |
      | description       | Another sample solution                      |
      | elibrary creation | 1                                            |
      | groups audience   | http://joinup.eu/collection/solution-api-bar |
    Then I should have 1 solution