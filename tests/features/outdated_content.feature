@javascript @api
Feature:
  - As a moderator I want to be able to manage the outdated content thresholds.
  - As a user, regardless of role, I want to see a notice on outdated content.

  Scenario: Test moderator and anonymous user perspectives.

    Given the following collections:
      | title            | state     |
      | Outdated content | validated |
    And the following solutions:
      | title             | collection       | state     |
      | Recent solution   | Outdated content | validated |
      | Outdated solution | Outdated content | validated |
    And discussion content:
      | title                         | collection       | publication date | created   | state     |
      | Recent published discussion   | Outdated content | -2 years         | -2 years  | validated |
      | Outdated published discussion | Outdated content | -11 years        | -11 years | validated |
      | Not published discussion      | Outdated content |                  | -12 years | draft     |
    And document content:
      | title             | collection       | document publication date | state     |
      | Very old document | Outdated content | 1985-03-04                | validated |
    And event content:
      | title                    | collection       | publication date          | created                   | state     |
      | Recent published event   | Outdated content | -3 months                 | -3 months                 | validated |
      | Outdated published event | Outdated content | -1 year -1 hour -1 second | -1 year -1 hour -1 second | validated |
      | Not published event      | Outdated content |                           | -31 years                 | draft     |
    And news content:
      | title                   | collection       | publication date  | created           | state     |
      | Recent published news   | Outdated content | -7 months         | -7 months         | validated |
      | Outdated published news | Outdated content | -3 years -1 month | -3 years -1 month | validated |
      | Not published news      | Outdated content |                   | -40 years         | draft     |

    Given I am logged in as a moderator
    When I click "Outdated content thresholds"
    Then I should see the heading "Outdated content thresholds"
    When I check the material checkbox in the "Discussion (Content)" table row
    And I fill in "config[node:discussion][threshold]" with "4"
    And I check the material checkbox in the "Event (Content)" table row
    And I fill in "config[node:event][threshold]" with "1"
    And I check the material checkbox in the "News (Content)" table row
    And I fill in "config[node:news][threshold]" with "1"
    When I press "Save configuration"

    # Test as anonymous. This covers all roles.
    Given I am an anonymous user

    # Discussion is prone to be outdated.
    When I go to the "Recent published discussion" discussion
    Then I should see the heading "Recent published discussion"
    But I should not see "This discussion is more than"

    When I go to the "Outdated published discussion" discussion
    Then I should see the heading "Outdated published discussion"
    And the text "This discussion is more than 11 years old" should appear 1 time

    # A document is not prone to be outdated.
    When I go to the "Very old document" document
    Then I should see the heading "Very old document"
    But I should not see "This document is more than"

    # Event is prone to be outdated.
    When I go to the "Recent published event" event
    Then I should see the heading "Recent published event"
    But I should not see "This event is more than"

    When I go to the "Outdated published event" event
    Then I should see the heading "Outdated published event"
    And the text "This event is more than 1 year old" should appear 1 time

    # News item is prone to be outdated.
    When I go to the "Recent published news" news
    Then I should see the heading "Recent published news"
    But I should not see "This news is more than"

    When I go to the "Outdated published news" news
    Then I should see the heading "Outdated published news"
    And the text "This news is more than 3 years old" should appear 1 time

    # Login as moderator to test content that was never published.
    Given I am logged in as a moderator

    # Content never published is not prone to be outdated.
    When I go to the "Not published discussion" discussion
    Then I should see the heading "Not published discussion"
    But I should not see "This discussion is more than"

    When I go to the "Not published event" event
    Then I should see the heading "Not published event"
    But I should not see "This event is more than"

    When I go to the "Not published news" news
    Then I should see the heading "Not published news"
    But I should not see "This news is more than"

    # Cleanup configuration.
    When I click "Outdated content thresholds"
    And I uncheck the material checkbox in the "Discussion (Content)" table row
    And I uncheck the material checkbox in the "Event (Content)" table row
    And I uncheck the material checkbox in the "News (Content)" table row
    When I press "Save configuration"
