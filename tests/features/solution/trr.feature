@api
Feature: Creating a test (solution) in the TRR collection.
  In order to create tests
  As a collection facilitator
  I need to be able to add 'test'-enabled solutions.
  @terms
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
    And I click "Add solution"
    And I should see the text "TRR"

    # Fill in basic solution data.
    When I fill in the following:
      | Title            | Linked Open Data                                              |
      | Description      | Re-usable government data                                     |
      | Spatial coverage | Belgium                                                       |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS |
      | Name             | Lucky Luke                                                    |
      | E-mail address   | ernsy1999@gmail.com                                           |
    Then I select "http://data.europa.eu/dr8/TestScenario" from "Solution type"
    And I select "Supplier exchange" from "Policy domain"
    # Attach a PDF to the documentation.
    And I upload the file "text.pdf" to "Upload a new file or enter a URL"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "W3C"
    And I press "Add owner"
    And I select "Completed" from "Status"

    # Fill in TRR specific data.
    And I select "Test Suite" from "Test resource type"
    And I select "Agent" from "Actor"
    And I fill in "Business process" with "Notification Of Failure"
    And I fill in "Product type" with "Soya beans"
    And I select "Level 1" from "Standardization level"
    Then I press "Propose"
    Then I should see the heading "Linked Open Data"

  Scenario: TRR distribution
    Given the following solution:
      | title         | TRR solution foo       |
      | description   | The test repository    |
      | state         | validated              |
      | solution type | [ABB130] Test Scenario |
    And the following solution:
      | title       | TRR solution bar    |
      | description | The test repository |
      | state       | validated           |
    And the following distribution:
      | title       | TRR Distribution foo                  |
      | description | Asset distribution sample description |
      | access url  | test.zip                              |
      | solution    | TRR solution foo                      |
    And the following distribution:
      | title       | TRR Distribution bar                  |
      | description | Asset distribution sample description |
      | access url  | test.zip                              |
      | solution    | TRR solution bar                      |
    And the following release:
      | title          | TRR release foo         |
      | description    | TRR release description |
      | documentation  | text.pdf                |
      | release number | 1                       |
      | release notes  | Changed release         |
      | distribution   | TRR Distribution foo    |
      | is version of  | TRR solution foo        |
    And the following release:
      | title          | TRR release bar         |
      | description    | TRR release description |
      | documentation  | text.pdf                |
      | release number | 1                       |
      | release notes  | Changed release         |
      | distribution   | TRR Distribution bar    |
      | is version of  | TRR solution bar        |

    # The GITB compliant field is only shown when the solution has a certain solution type.
    When I am logged in as a "facilitator" of the "TRR solution foo" solution
    When I go to the "TRR Distribution foo" asset distribution edit form
    Then the following fields should be present "GITB compliant"

    When I go to the homepage of the "TRR release foo" release
    And I click "Add distribution" in the plus button menu
    Then the following fields should be present "GITB compliant"

    When I am logged in as a "facilitator" of the "TRR solution bar" solution
    When I go to the "TRR Distribution bar" asset distribution edit form
    Then the following fields should not be present "GITB compliant"

    When I go to the homepage of the "TRR release bar" release
    And I click "Add distribution" in the plus button menu
    Then the following fields should not be present "GITB compliant"
