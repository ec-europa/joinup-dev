@api
Feature:
  As a moderator
  When I urgently need to import some federated content
  I want to be able to instantly go to the ADMS-AP importer

  Scenario: Access the ADMS-AP importer through the toolbar
    Given I am logged in as a moderator
    And I am on the homepage
    When I click "ADMS-AP importer"
    Then I should see the heading "Select pipeline"

  Scenario: Other user roles do not see the link
    Given I am an anonymous user
    And I am on the homepage
    Then I should not see the link "ADMS-AP importer"

    Given I am logged in as a user with the "authenticated" role
    And I am on the homepage
    Then I should not see the link "ADMS-AP importer"
