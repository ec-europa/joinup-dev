@api @group-b
Feature: Site search
  As an analytics engineer
  I want search statistics grouped by the keyword
  So that I can better understand the needs of our users

  Scenario: Search keywords are tracked in analytics.
    When I am on the homepage
    And I enter "sample text 1" in the header search bar and hit enter
    Then the response should contain "\"search\":{\"keyword\":\"sample text 1\"}"
