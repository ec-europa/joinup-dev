@api
Feature: In order to avoid users changing federated values
  As a site owner
  I want federated entities to have the schema values disabled.

  Background: A provenance activity can be created through the API.
    Given the following owner:
      | name           |
      | John Federator |
    And the following contact:
      | name  | John Federator's contact  |
      | email | JohnFederator@example.com |
    And the following licence:
      | title       | A federated licence       |
      | description | Licence agreement details |
      | type        | Public domain             |
    And the following solution:
      | title               | A federated solution                 |
      | description         | This is a federated solution         |
      | owner               | John Federator                       |
      | contact information | John Federator's contact             |
      | documentation       | text.pdf                             |
      | elibrary creation   | registered users                     |
      | landing page        | http://foo-example.com/landing       |
      | webdav creation     | no                                   |
      | webdav url          | http://joinup.eu/solution/foo/webdav |
      | wiki                | http://example.wiki/foobar/wiki      |
      | state               | validated                            |
    And the following collection:
      | title               | A federated collection   |
      | logo                | logo.png                 |
      | moderation          | yes                      |
      | owner               | John Federator           |
      | contact information | John Federator's contact |
      | elibrary creation   | facilitators             |
      | affiliates          | A federated solution     |
      | state               | validated                |
    And the following distribution:
      | title       | A federated distribution         |
      | description | This is a federated distribution |
      | access url  | test.zip                         |
      | solution    | A federated solution             |
      | licence     | A federated licence              |
    And the following release:
      | title          | A federated release         |
      | description    | This is a federated release |
      | documentation  | text.pdf                    |
      | release number | 1                           |
      | release notes  | Changed release             |
      | distribution   | A federated distribution    |
      | is version of  | A federated solution        |
      | state          | validated                   |
    And the following provenance activities:
      | associated with    | entity                   | enabled |
      | http://example.com | A federated solution     | yes     |
      | http://example.com | A federated collection   | yes     |
      | http://example.com | A federated release      | yes     |
      | http://example.com | A federated distribution | yes     |
      | http://example.com | John Federator           | yes     |
      | http://example.com | John Federator's contact | yes     |
      | http://example.com | A federated licence      | yes     |

  Scenario: Verify that API functions properly.
    Then I should have 7 provenance activity
    And the "A federated collection" "collection" should have a provenance activity related
    And the "A federated solution" "solution" should have a provenance activity related
    And the "A federated release" "asset release" should have a provenance activity related
    And the "A federated distribution" "asset distribution" should have a provenance activity related
    And the "John Federator" "owner" should have a provenance activity related
    And the "John Federator's contact" "contact information" should have a provenance activity related
    And the "A federated licence" "licence" should have a provenance activity related

  Scenario Outline: Schema fields are disabled for federated entities.
    When I am logged in as a moderator
    And I go to the "<label>" <type>
    And I click "Edit" in the "Entity actions" region
    Then the following fields should be disabled "<fields disabled>"
    And the following fields should not be disabled "<fields not disabled>"

    Examples:
      | label                    | type         | fields disabled                                                                                              | fields not disabled                                                                                            |
      | A federated collection   | collection   | Title, Description, Contact information, Owner                                                               | Abstract, Access URL, Policy domain, Moderated, eLibrary creation, Motivation, Logo, Banner, Closed collection |
      | A federated solution     | solution     | Title, Description, Contact information, Owner, Keywords, Related solutions, Status, Languages, Landing page | Policy domain, Moderated, eLibrary creation, Motivation, Logo, Banner, Metrics pager                           |
      | A federated release      | release      | Name, Release number, Keywords, Status, Language                                                             | Motivation                                                                                                     |
      | A federated distribution | distribution | Title, Description, Access URL, Format, Status                                                               |                                                                                                                |
      | John Federator           | owner        | Name                                                                                                         |                                                                                                                |
      | John Federator's contact | contact      | E-mail address, Name, Website URL                                                                            |                                                                                                                |
