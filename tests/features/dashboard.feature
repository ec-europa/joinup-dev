@api @group-d
Feature: Dashboard
  In order to see an overview of my related information at a glance
  As an authenticated user
  I should have access to a dashboard

  Scenario: Access the dashboard
    Given I am logged in as a "moderator"
    When I am on the homepage
    Then I should see the link "Dashboard"
    When I click "Dashboard"
    Then I should see the heading "Dashboard"
