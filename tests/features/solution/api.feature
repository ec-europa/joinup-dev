@api
Feature: Solution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a solution
    Given the following solution:
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
      | state             | validated                            |
    And the following collection:
      | title             | Solution API foo  |
      | logo              | logo.png          |
      | moderation        | yes               |
      | elibrary creation | facilitators      |
      | affiliates        | My first solution |
      | state             | validated         |
    Then I should have 1 solution

  Scenario: Programmatically create a solution using only the mandatory fields
    Given the following solution:
      | title             | My first solution mandatory |
      | description       | Another sample solution     |
      | elibrary creation | members                     |
      | state             | validated                   |
    And the following collection:
      | title             | Solution API bar            |
      | logo              | logo.png                    |
      | moderation        | yes                         |
      | elibrary creation | facilitators                |
      | affiliates        | My first solution mandatory |
      | state             | validated                   |
    Then I should have 1 solution

  @terms
  Scenario: Assign ownership during creation of solutions through UI
    Given the following owner:
      | name      | type            |
      | Leechidna | Local Authority |
    And users:
      | Username          | Password |
      | Solution API user | pass     |
    And the following collection:
      | title             | This is a klm collection |
      | logo              | logo.png                 |
      | banner            | banner.jpg               |
      | moderation        | no                       |
      | closed            | no                       |
      | elibrary creation | facilitators             |
      | state             | validated                |
    And the following collection user memberships:
      | user              | collection               | roles       |
      | Solution API user | This is a klm collection | facilitator |
    And I am logged in as "Solution API user"
    When I visit the "This is a klm collection" collection
    # And I click on element ".mdl-button__ripple-container"
    Then I should see the link "Add solution"
    And I click "Add solution"
    When I fill in the following:
      | Title          | Solution API example                         |
      | Description    | We do not care that much about descriptions. |
      | Name           | Gopheadow                                    |
      | E-mail address | solutionAPI@example.com                      |
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I press "Add existing" at the "Owner" field
    # Then I wait for AJAX to finish
    And I fill in "Owner" with "Leechidna"
    And I fill in "Language" with "http://publications.europa.eu/resource/authority/language/VLS"
    And I select "EU and European Policies" from "Policy domain"
    And I select "[ABB8] Citizen" from "Solution type"
    And I press "Add owner"
    And I press "Save"
    Then I should see the heading "Solution API example"
    And the user "Solution API user" should be the owner of the "Solution API example" solution

    # Cleanup step.
    Then I delete the "Solution API example" solution
