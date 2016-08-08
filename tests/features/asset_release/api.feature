Feature: Solution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a solution
    Given the following solution:
      | title             | My first solution                    |
      | description       | A sample solution                    |
      | documentation     | text.pdf                             |
      | elibrary creation | registered users                     |
      | landing page      | http://foo-example.com/landing       |
      | webdav creation   | no                                   |
      | webdav url        | http://joinup.eu/solution/foo/webdav |
      | wiki              | http://example.wiki/foobar/wiki      |
    And the following collection:
      | title             | Solution API foo  |
      | logo              | logo.png          |
      | moderation        | yes               |
      | elibrary creation | facilitators      |
      | affiliates        | My first solution |
    And the following asset release:
      | title          | My first release  |
      | description    | A sample release  |
      | documentation  | text.pdf          |
      | release number | 1                 |
      | release notes  | Changed release   |
      | is version of  | My first solution |
    Then I should have 1 asset release

  Scenario: Programmatically create a collection using only the mandatory fields
    Given the following solution:
      | title             | My first solution mandatory |
      | description       | Another sample solution     |
      | elibrary creation | members                     |
    And the following collection:
      | title             | Solution API bar            |
      | logo              | logo.png                    |
      | moderation        | yes                         |
      | elibrary creation | facilitators                |
      | affiliates        | My first solution mandatory |
    And the following asset release:
      | title          | My first mandatory release  |
      | description    | A sample release            |
      | release number | 3                           |
      | release notes  | Changed release             |
      | is version of  | My first solution mandatory |
    Then I should have 1 asset release
