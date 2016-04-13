@api
Feature: Solution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a solution
    Given the following collection:
      | title             | Asset distribution collection API foo |
      | logo              | logo.png                              |
      | moderation        | yes                                   |
      | closed            | yes                                   |
      | elibrary creation | facilitators                          |
    And the following asset distribution:
      | title       | Asset distribution entity foo         |
      | description | Asset distribution sample description |
      | file        | test.zip                              |
    And the following solution:
      | title             | Asset distribution solution           |
      | description       | Asset distribution sample solution    |
      | documentation     | text.pdf                              |
      | elibrary creation | registered users                      |
      | landing page      | http://foo-example.com/landing        |
      | webdav creation   | no                                    |
      | webdav url        | http://joinup.eu/solution/foo/webdav  |
      | wiki              | http://example.wiki/foobar/wiki       |
      | distribution      | Asset distribution entity foo         |
      | collection        | Asset distribution collection API foo |
    Then I should have 1 solution
    And I should have 1 asset distribution
    And the "Asset distribution entity foo" asset distribution is related to the "Asset distribution solution" solution

  Scenario: Programmatically create a collection using only the mandatory fields
    Given the following collection:
      | title             | Asset distribution short API bar |
      | logo              | logo.png                         |
      | moderation        | yes                              |
      | closed            | yes                              |
      | elibrary creation | facilitators                     |
    And the following asset distribution:
      | title       | Asset distribution entity foo short   |
      | description | Asset distribution sample description |
      | file        | test.zip                              |
    Given the following solution:
      | title             | AD first solution mandatory short   |
      | description       | Another sample solution             |
      | elibrary creation | members                             |
      | distribution      | Asset distribution entity foo short |
      | collection        | Asset distribution short API bar    |
    Then I should have 1 solution
    And I should have 1 asset distribution
    And the "Asset distribution entity foo short" asset distribution is related to the "AD first solution mandatory short" solution