Feature: Solution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a solution
    Given the following collection:
      | title             | Solution API foo |
      | logo              | logo.png         |
      | moderation        | yes              |
      | closed            | yes              |
      | elibrary creation | facilitators     |
    And the following solution:
      | title             | My first solution                    |
      | description       | A sample solution                    |
      | logo              | logo.png                             |
      | banner            | banner.jpg                           |
      | documentation     | text.pdf                             |
      | elibrary creation | registered users                     |
      | landing page      | http://foo-example.com/landing       |
      | webdav creation   | no                                   |
      | webdav url        | http://joinup.eu/solution/foo/webdav |
      | wiki              | http://example.wiki/foobar/wiki      |
      | collection        | Solution API foo                     |
    Then I should have 1 solution

  Scenario: Programmatically create a solution using only the mandatory fields
    Given the following collection:
      | title             | Solution API bar |
      | logo              | logo.png         |
      | moderation        | yes              |
      | closed            | yes              |
      | elibrary creation | facilitators     |
    Given the following solution:
      | title             | My first solution mandatory       |
      | description       | Another sample solution           |
      | logo              | logo.png                          |
      | banner            | banner.jpg                        |
      | elibrary creation | members                           |
      | collection        | Solution API bar                  |
    Then I should have 1 solution