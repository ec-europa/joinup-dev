@api
Feature: Release API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Release API

  Scenario: Programmatically create a solution
    Given the following collection:
      | title             | Solution API foo |
      | logo              | logo.png         |
      | moderation        | yes              |
      | elibrary creation | facilitators     |
      | state             | validated        |
    And the following solution:
      | title             | My first solution                    |
      | collection        | Solution API foo                     |
      | description       | A sample solution                    |
      | documentation     | text.pdf                             |
      | elibrary creation | registered users                     |
      | landing page      | http://foo-example.com/landing       |
      | webdav creation   | no                                   |
      | webdav url        | http://joinup.eu/solution/foo/webdav |
      | wiki              | http://example.wiki/foobar/wiki      |
      | state             | validated                            |
    And the following release:
      | title          | My first release  |
      | description    | A sample release  |
      | documentation  | text.pdf          |
      | release number | 1                 |
      | release notes  | Changed release   |
      | is version of  | My first solution |
    Then I should have 1 release

  Scenario: Programmatically create a collection using only the mandatory fields
    Given the following collection:
      | title             | Solution API bar |
      | logo              | logo.png         |
      | moderation        | yes              |
      | elibrary creation | facilitators     |
      | state             | validated        |
    And the following solution:
      | title             | My first solution mandatory |
      | collection        | Solution API bar            |
      | description       | Another sample solution     |
      | elibrary creation | members                     |
      | state             | validated                   |
    And the following release:
      | title          | My first mandatory release  |
      | description    | A sample release            |
      | release number | 3                           |
      | release notes  | Changed release             |
      | is version of  | My first solution mandatory |
    Then I should have 1 release
