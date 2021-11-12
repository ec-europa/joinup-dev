@api @group-g
Feature: In order to help moderators manage effectively imported content
  As the owner of the website
  I need to show status messages if a pipeline has not run for too long.

  Scenario: Show a status message to moderators upon login for pipelines that have not run for too long.
    Given no pipelines have run
    When the "Joinup collection" pipeline was last executed 89 days ago
    And the "Slovenian Interoperability Portal - NIO" pipeline was last executed 92 days ago
    When I am logged in as a moderator
    Then I should not see the warning message "Pipeline Joinup collection has not been executed for 89 days."
    But I should see the warning message "Pipeline Slovenian Interoperability Portal - NIO has not been executed for 92 days."
    Given I reload the page
    Then I should not see the warning message "Pipeline Joinup collection has not been executed for 89 days."
    And I should not see the warning message "Pipeline Slovenian Interoperability Portal - NIO has not been executed for 92 days."

    Given the "Joinup collection" pipeline was last executed 91 days ago
    When I am not logged in
    And I am logged in as a moderator
    Then I should see the warning message "Pipeline Joinup collection has not been executed for 91 days."
    Then I should see the warning message "Pipeline Slovenian Interoperability Portal - NIO has not been executed for 92 days."
    When I reload the page
    Then I should not see the warning message "Pipeline Joinup collection has not been executed for 91 days."
    Then I should not see the warning message "Pipeline Slovenian Interoperability Portal - NIO has not been executed for 92 days."

    When I go to "/admin/reporting"
    And I click "Pipeline report"
    Then the "pipeline log" table should contain the following columns:
      | Pipeline                                 | Last executed |
      | Danish Public Sector Interoperability    | Never         |
      | EU Schemantic Interoperability Catalogue | Never         |
      | Joinup collection                        | 91 days ago   |
      | Slovenian Interoperability Portal - NIO  | 92 days ago   |
      | Spain - Center for Technology Transfer   | Never         |
