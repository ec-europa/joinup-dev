@api @group-g
Feature: Creating a test (solution) in the TRR collection.
  In order to create tests
  As a collection facilitator
  I need to be able to add 'test'-enabled solutions.

  @terms @javascript
  Scenario: Create a TRR solution
    Given users:
      | Username | Roles |
      | Wobbe    |       |
    Given the following collection:
      | title | Friends of the test repository |
      | state | validated                      |
    And the following collection user memberships:
      | collection                     | user  | roles |
      | Friends of the test repository | Wobbe | owner |
    And the following owner:
      | name | type                         |
      | W3C  | Company, Industry consortium |
    When I am logged in as "Wobbe"
    Given I go to the homepage of the "Friends of the test repository" collection
    And I open the plus button menu
    And I click "Add solution"
    And I check the "I have read and accept the legal notice and I commit to manage my solution on a regular basis." material checkbox
    And I press "Yes"
    And I should see the text "Add solution"

    # Fill in basic solution data.
    When I fill in "Title" with "Linked Open Data"
    And I enter "Re-usable government data" in the "Description" wysiwyg editor
    When I fill in the following:
      | Name           | Lucky Luke          |
      | E-mail address | ernsy1999@gmail.com |
    And I select "Supplier exchange" from "Topic"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I wait for AJAX to finish
    And I fill in "Owner" with "W3C"
    And I press "Add owner"
    And I wait for AJAX to finish

    # TRR fields should be hidden by default.
    Given the following fields should not be visible "Test resource type, Actor, Business process, Product type, Standardization level"
    # A "TRR" solution is unlocked by choosing one of the following solution types:
    # - Conformance Testing Service
    # - Conformance Testing Component
    # - Conformance Test Scenario
    When I select "Conformance Testing Service" from "Solution type"
    Then the following fields should be visible "Test resource type, Actor, Business process, Product type, Standardization level"
    # TRR solutions have additional required fields.
    When I press "Propose"
    Then I should see the following error messages:
      | error messages                                                                                        |
      | The field Test resource type is required when Solution type is set to Conformance Testing Service.    |
      | The field Actor is required when Solution type is set to Conformance Testing Service.                 |
      | The field Business process is required when Solution type is set to Conformance Testing Service.      |
      | The field Product type is required when Solution type is set to Conformance Testing Service.          |
      | The field Standardization level is required when Solution type is set to Conformance Testing Service. |

    # Fill in TRR specific data.
    When I select "Agent" from "Actor"
    And I fill in "Business process" with "Notification Of Failure"
    And I fill in "Product type" with "Soya beans"
    And I select "Level 1" from "Standardization level"

    # "Test resource type" allowed values vary based on the solution type field.
    When I select "Test Suite" from "Test resource type"
    And I press "Propose"
    Then I should see the error message 'Test resource type should be either "Test Bed", "Messaging Adapter" or "Document Validator" when solution type is set to "Test service" or "Conformance Testing Component".'
    When I select "Test Scenario" from "Solution type"
    And I select "Messaging Adapter" from "Test resource type"
    And I press "Propose"
    Then I should see the error message 'Test resource type should be either "Test Suite", "Test Case", "Test Assertion" or "Document Assertion Set" when solution type is set to "Conformance Test Scenario".'
    When I select "Test Suite" from "Test resource type"
    And I press "Propose"
    Then I should see the heading "Linked Open Data"

  Scenario: TRR distribution
    Given the following solution:
      | title         | TRR solution foo          |
      | description   | The test repository       |
      | state         | validated                 |
      | solution type | Conformance Test Scenario |
    And the following solution:
      | title       | TRR solution bar    |
      | description | The test repository |
      | state       | validated           |
    And the following release:
      | title          | TRR release foo         |
      | description    | TRR release description |
      | documentation  | text.pdf                |
      | release number | 1                       |
      | release notes  | Changed release         |
      | is version of  | TRR solution foo        |
    And the following release:
      | title          | TRR release bar         |
      | description    | TRR release description |
      | documentation  | text.pdf                |
      | release number | 1                       |
      | release notes  | Changed release         |
      | is version of  | TRR solution bar        |
    And the following distribution:
      | title       | TRR Distribution foo                  |
      | description | Asset distribution sample description |
      | access url  | test.zip                              |
      | parent      | TRR release foo                       |
    And the following distribution:
      | title       | TRR Distribution bar                  |
      | description | Asset distribution sample description |
      | access url  | test.zip                              |
      | parent      | TRR release bar                       |

    # The GITB compliant field is only shown when the solution has a certain solution type.
    When I am logged in as a "facilitator" of the "TRR solution foo" solution
    When I go to the edit form of the "TRR Distribution foo" distribution
    Then the following fields should be present "GITB compliant"

    When I go to the homepage of the "TRR release foo" release
    And I click "Add distribution" in the plus button menu
    Then the following fields should be present "GITB compliant"

    When I am logged in as a "facilitator" of the "TRR solution bar" solution
    When I go to the edit form of the "TRR Distribution foo" distribution
    Then the following fields should not be present "GITB compliant"

    When I go to the homepage of the "TRR release bar" release
    And I click "Add distribution" in the plus button menu
    Then the following fields should not be present "GITB compliant"
