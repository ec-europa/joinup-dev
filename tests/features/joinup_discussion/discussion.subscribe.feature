@api
Feature: Subscribing to discussions
  As a user of the website
  I want to subscribe to discussions
  So that I can up to date with its evolvements.

  Scenario: Subscribe to a discussion.
    Given the following collection:
      | title | Dairy products |
      | state | validated      |
    And discussion content:
      | title       | body                                                             | collection     | state     |
      | Rare Butter | I think that the rarest butter out there is the milky way butter | Dairy products | validated |

    When I am an anonymous user
    And I go to the "Rare Butter" discussion
    Then I should not see the link "Subscribe"
    And I should not see the link "Unsubscribe"

    When I am logged in as an "authenticated user"
    And I go to the "Rare Butter" discussion
    Then I should see the link "Subscribe"
    And I should not see the link "Unsubscribe"

    When I click "Subscribe"
    Then I should see the link "Unsubscribe"

    When I click "Unsubscribe"
    Then I should see the heading "Unsubscribe from this discussion?"
    When I press "Unsubscribe"
    Then I should see the heading "Rare Butter"
    And I should see the link "Subscribe"
