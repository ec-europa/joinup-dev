@api @group-c
Feature: Revoke privileges from former and blocked members
  In order to ensure content in my group is not vandalized by former members
  As a facilitator
  I need to be able to block or remove members while keeping their content

  Background:
    Given users:
      | Username  |
      | Lord Hong |
    And the following collections:
      | title             | state     |
      | Interesting times | validated |
    And the following collection user memberships:
      | collection        | user      |
      | Interesting times | Lord Hong |
    And news content:
      | title             | body               | author    | state     | collection        |
      | Join the Red Army | Free the prisoners | Lord Hong | validated | Interesting times |

  Scenario: A blocked user cannot edit their own group content.
    Given I am logged in as "Lord Hong"
    When I go to the edit form of the "Join the Red Army" news
    Then I should see the heading "Edit News Join the Red Army"

    Given my membership state in the "Interesting times" collection changes to blocked
    When I go to the edit form of the "Join the Red Army" news
    Then I should get an access denied error
