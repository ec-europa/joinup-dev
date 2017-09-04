@api
Feature: Asset distribution editing.
  In order to manage asset distributions
  As a solution owner or solution facilitator
  I need to be able to edit asset distributions through UI.

  Background:
    Given users:
      | Username     | E-mail                   | First name | Family name | Roles |
      | Gregg Hill   | Gregg.Hill@example.com   | Gregg      | Hill        |       |
      | Pedro Torres | Pedro.Torres@example.com | Pedro      | Torres      |       |
    And the following solutions:
      | title      | description        | state     |
      | Solution A | Sample description | validated |
      | Solution B | Sample description | validated |
    And the following collection:
      | title      | Collection example     |
      | affiliates | Solution A, Solution B |
      | state      | validated              |
    And the following licence:
      | title       | LGPL                                |
      | description | The LGPL more permisssive than GPL. |
    And the following release:
      | title          | Asset release example |
      | release number | C3PO                  |
      | description    | Release description   |
      | is version of  | Solution A            |
    And the following distribution:
      | title       | Asset distribution example |
      | description | Sample description         |
      | licence     | LGPL                       |
      | access url  | test.zip                   |
      | solution    | Solution A                 |
      | parent      | Asset release example      |
    And the following solution user membership:
      | solution   | user         | roles       |
      | Solution A | Gregg Hill   | owner       |
      | Solution A | Pedro Torres | facilitator |

  Scenario: "Edit" button should be shown to facilitators of the related solution.
    When I am logged in as a facilitator of the "Solution A" solution
    And I go to the homepage of the "Asset distribution example" asset distribution
    Then I should see the link "Edit" in the "Entity actions" region

    When I am logged in as a facilitator of the "Solution B" solution
    And I go to the homepage of the "Asset distribution example" asset distribution
    Then I should not see the link "Edit" in the "Entity actions" region

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Asset distribution example" asset distribution
    Then I should not see the link "Edit" in the "Entity actions" region

    When I am an anonymous user
    And I go to the homepage of the "Asset distribution example" asset distribution
    Then I should not see the link "Edit" in the "Entity actions" region

  @email
  Scenario: Edit a distribution as a solution facilitator.
    When all e-mails have been sent
    When I am logged in as a facilitator of the "Solution A" solution
    And I go to the homepage of the "Asset distribution example" asset distribution
    And I click "Edit"
    Then I should see the heading "Edit Distribution Asset distribution example"
    And the following fields should not be present "Langcode, Translation"
    When I fill in "Title" with "Asset distribution example revised"
    # Set a non-HTTP protocol remote URL.
    And I press the "Remove" button
    And I set a remote URL "ftp://example.com/file.txt" to "Access URL"
    And I press "Save"
    Then I should see the heading "Asset distribution example revised"
    And the following email should have been sent:
      | recipient | Gregg Hill                                                                                                                                          |
      | subject   | Joinup: A distribution has been updated                                                                                                             |
      | body      | The distribution Asset distribution example revised of the release Asset release example, C3PO of the solution Solution A was successfully updated. |
    And the following email should have been sent:
      | recipient | Pedro Torres                                                                                                                                        |
      | subject   | Joinup: A distribution has been updated                                                                                                             |
      | body      | The distribution Asset distribution example revised of the release Asset release example, C3PO of the solution Solution A was successfully updated. |
