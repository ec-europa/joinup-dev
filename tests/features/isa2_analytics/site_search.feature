@api @group-b
Feature: Site search
  As an analytics engineer
  I want search statistics grouped by the keyword
  So that I can better understand the needs of our users

  Scenario: Search keywords are tracked in analytics.
    When I am on the homepage
    When I enter "sample text 1" in the search bar and press enter
    Then the response should contain "\"search\":{\"keyword\":\"sample text 1\"}"
