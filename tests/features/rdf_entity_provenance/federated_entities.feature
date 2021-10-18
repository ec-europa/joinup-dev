@api @group-e
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
    And the following collection:
      | title               | A federated collection   |
      | logo                | logo.png                 |
      | moderation          | yes                      |
      | owner               | John Federator           |
      | contact information | John Federator's contact |
      | content creation    | facilitators and authors |
      | state               | validated                |
    And the following solution:
      | title               | A federated solution                 |
      | collection          | A federated collection               |
      | description         | This is a federated solution         |
      | owner               | John Federator                       |
      | contact information | John Federator's contact             |
      | documentation       | text.pdf                             |
      | content creation    | registered users                     |
      | landing page        | http://foo-example.com/landing       |
      | webdav creation     | no                                   |
      | webdav url          | http://joinup.eu/solution/foo/webdav |
      | wiki                | http://example.wiki/foobar/wiki      |
      | state               | validated                            |
    And the following release:
      | title          | A federated release         |
      | description    | This is a federated release |
      | documentation  | text.pdf                    |
      | release number | 1                           |
      | release notes  | Changed release             |
      | is version of  | A federated solution        |
      | state          | validated                   |
    And the following distribution:
      | title       | A federated distribution         |
      | description | This is a federated distribution |
      | access url  | test.zip                         |
      | parent      | A federated release              |
      | licence     | A federated licence              |
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
    Then I should have 7 provenance activities
    And "A federated collection" should have a related provenance activity
    And "A federated solution" should have a related provenance activity
    And "A federated release" should have a related provenance activity
    And "A federated distribution" should have a related provenance activity
    And "John Federator" should have a related provenance activity
    And "John Federator's contact" should have a related provenance activity

  Scenario Outline: Schema fields are disabled for federated entities.
    When I am logged in as a moderator
    And I go to the "<label>" <type>
    And I click "Edit" in the "Entity actions" region
    Then the following fields should be disabled "<fields disabled>"
    And the following fields should not be disabled "<fields not disabled>"

    Examples:
      | label                    | type         | fields disabled                                                                                              | fields not disabled                                                                                   |
      | A federated collection   | collection   | Title, Description, Contact information, Owner                                                               | Abstract, Access URL, Topic, Moderated, Content creation, Motivation, Logo, Banner, Closed collection |
      | A federated solution     | solution     | Title, Description, Contact information, Owner, Keywords, Related solutions, Status, Languages, Landing page | Topic, Moderated, Content creation, Motivation, Logo, Banner, Metrics pager                           |
      | A federated release      | release      | Name, Release number, Keywords, Status, Language                                                             | Motivation                                                                                            |
      | A federated distribution | distribution | Title, Description, Access URL, Format, Status, Licence                                                      |                                                                                                       |
      | John Federator           | owner        | Name                                                                                                         |                                                                                                       |
      | John Federator's contact | contact      | E-mail address, Name, Website URL                                                                            |                                                                                                       |
