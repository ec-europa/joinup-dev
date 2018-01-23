@api
Feature:
  As a site moderator/administrator
  When I'm logged in
  I want to be able to access the Joinup reporting section.

  Scenario Outline: Test the general access to Reporting section.
    Given I am logged in as a <role>
    And I am on the homepage
    When I am on "/admin/reporting"
    Then I should get a <code> HTTP response

    Examples:
      | role          | code |
      | authenticated | 403  |
      | administrator | 200  |
      | moderator     | 200  |

  Scenario: Solutions by type
    Given I am logged in as a moderator
    When I am on "/admin/reporting"
    Then I click "Solutions by solution type"
    Then I should get a 200 HTTP response
