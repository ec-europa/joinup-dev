@api
Feature: Asset distribution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a distribution
    Given the following solution:
      | title             | Asset distribution solution          |
      | description       | Asset distribution sample solution   |
      | documentation     | text.pdf                             |
      | elibrary creation | registered users                     |
      | landing page      | http://foo-example.com/landing       |
      | webdav creation   | no                                   |
      | webdav url        | http://joinup.eu/solution/foo/webdav |
      | wiki              | http://example.wiki/foobar/wiki      |
    And the following collection:
      | title             | Asset distribution collection API foo |
      | logo              | logo.png                              |
      | moderation        | yes                                   |
      | elibrary creation | facilitators                          |
      | affiliates        | Asset distribution solution           |
    And the following asset distribution:
      | title       | Asset distribution entity foo         |
      | description | Asset distribution sample description |
      | file        | test.zip                              |
      | solution    | Asset distribution solution           |
    And the following asset release:
      | title          | Asset distribution asset release   |
      | description    | Asset distribution sample solution |
      | documentation  | text.pdf                           |
      | release number | 1                                  |
      | release notes  | Changed release                    |
      | distribution   | Asset distribution entity foo      |
      | is version of  | Asset distribution solution        |
    Then I should have 1 solution
    And I should have 1 asset release
    And I should have 1 asset distribution

  Scenario: Programmatically create a distribution using only the mandatory fields
    Given the following solution:
      | title             | AD first solution mandatory short |
      | description       | Another sample solution           |
      | elibrary creation | members                           |
    And the following collection:
      | title             | Asset distribution short API bar  |
      | logo              | logo.png                          |
      | moderation        | yes                               |
      | elibrary creation | facilitators                      |
      | affiliates        | AD first solution mandatory short |
    And the following asset distribution:
      | title       | Asset distribution entity foo short   |
      | description | Asset distribution sample description |
      | file        | test.zip                              |
      | solution    | AD first solution mandatory short     |
    And the following asset release:
      | title          | AD first release                    |
      | description    | Asset distribution sample solution  |
      | distribution   | Asset distribution entity foo short |
      | release number | 1                                   |
      | release notes  | Changed release                     |
      | is version of  | AD first solution mandatory short   |
    Then I should have 1 solution
    And I should have 1 asset release
    And I should have 1 asset distribution
