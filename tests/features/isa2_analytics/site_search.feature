@api @group-f
Feature: Site search
  As an analytics engineer
  I want search statistics grouped by the keyword
  So that I can better understand the needs of our users

  Scenario: Search keywords are tracked in analytics.
    When I am on the homepage
    When I enter "sample text 1" in the search bar and press enter
    # The result count is 0, thus, is not added.
    Then the response should contain "\"search\":{\"keyword\":\"sample text 1\"}"

  Scenario: Search results should contain a total count when results are available.
    And the following collections:
      | title                  | description               | abstract               | state     |
      | Collection total count | No description available. | No abstract available. | validated |
    And news content:
      | title                | headline             | body               | collection             | state     |
      | News total count     | News total count     | No body available. | Collection total count | validated |
      | News total count new | News total count new | No body available. | Collection total count | draft     |
    When I am on the homepage
    And I enter "total count" in the search bar and press enter
    Then I should see the following tiles in the correct order:
      | Collection total count |
      | News total count       |
    And the response should contain "\"search\":{\"keyword\":\"total count\",\"count\":2}"

    # Test as different user and change the data to ensure that cache is invalidated properly.
    When I am logged in as a moderator
    And I enter "total count" in the search bar and press enter
    Then I should see the following tiles in the correct order:
      | Collection total count |
      | News total count       |
    And the response should contain "\"search\":{\"keyword\":\"total count\",\"count\":2}"

    When I go to the "News total count new" news
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    And I enter "total count" in the search bar and press enter
    Then I should see the following tiles in the correct order:
      | Collection total count |
      | News total count       |
      | News total count new   |
    And the response should contain "\"search\":{\"keyword\":\"total count\",\"count\":3}"
