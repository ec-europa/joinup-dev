@api @group-e
Feature: Solution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a solution
    Given the following collection:
      | title            | Solution API foo         |
      | logo             | logo.png                 |
      | moderation       | yes                      |
      | content creation | facilitators and authors |
      | state            | validated                |
    And the following solution:
      | title            | My first solution                    |
      | collection       | Solution API foo                     |
      | description      | A sample solution                    |
      | logo             | logo.png                             |
      | banner           | banner.jpg                           |
      | documentation    | text.pdf                             |
      | content creation | registered users                     |
      | landing page     | http://foo-example.com/landing       |
      | webdav creation  | no                                   |
      | webdav url       | http://joinup.eu/solution/foo/webdav |
      | wiki             | http://example.wiki/foobar/wiki      |
      | state            | validated                            |
    Then I should have 1 solution

  Scenario: Programmatically create a solution using only the mandatory fields
    Given the following collection:
      | title            | Solution API bar         |
      | logo             | logo.png                 |
      | moderation       | yes                      |
      | content creation | facilitators and authors |
      | state            | validated                |
    And the following solution:
      | title            | My first solution mandatory |
      | collection       | Solution API bar            |
      | description      | Another sample solution     |
      | content creation | registered users            |
      | state            | validated                   |
    Then I should have 1 solution

  Scenario: Programmatically create a solution that is affiliated with a collection
    Given the following collection:
      | title | Inflatable mascots |
      | state | validated          |
    And the following solution:
      | title       | Inflatable rooster             |
      | description | For placing near a white house |
      | state       | validated                      |
      | collection  | Inflatable mascots             |
    Then I should have 1 solution
    And the "Inflatable rooster" solution should be affiliated with the "Inflatable mascots" collection

  @terms @uploadFiles:logo.png,banner.jpg
  Scenario: Assign ownership during creation of solutions through UI
    Given the following owner:
      | name      | type            |
      | Leechidna | Local Authority |
    And users:
      | Username          |
      | Solution API user |
    And the following collection:
      | title            | This is a klm collection |
      | logo             | logo.png                 |
      | banner           | banner.jpg               |
      | moderation       | no                       |
      | closed           | no                       |
      | content creation | facilitators and authors |
      | state            | validated                |
    And the following collection user memberships:
      | user              | collection               | roles       |
      | Solution API user | This is a klm collection | facilitator |
    And I am logged in as "Solution API user"
    When I visit the "This is a klm collection" collection
    # And I click on element ".mdl-button__ripple-container"
    Then I should see the link "Add solution"
    And I click "Add solution"
    And I check "I have read and accept the legal notice and I commit to manage my solution on a regular basis."
    And I press "Yes"
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
    And I select "EU and European Policies" from "Topic"
    And I select "Citizen" from "Solution type"
    And I press "Add owner"
    And I press "Save"
    Then I should see the heading "Solution API example"
    And the user "Solution API user" should be the owner of the "Solution API example" solution

    # Cleanup step.
    Then I delete the "Solution API example" solution
