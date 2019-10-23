@api
Feature: As a privileged user
  I want to share solutions to my collections
  So that useful information has more visibility

  Scenario: Share link is visible for privileged users.
    Given collections:
      | title                        | logo     | state     |
      | Collection share original    | logo.png | validated |
      | Collection share candidate 1 | logo.png | validated |
      | Collection share candidate 2 | logo.png | validated |
    And the following solutions:
      | title                 | description         | logo     | banner     | state     | collection                |
      | Solution to be shared | Doesn't affect test | logo.png | banner.jpg | validated | Collection share original |
    Given I am an anonymous user
    And I go to the homepage of the "Collection share original" collection
    Then I should see the "Solution to be shared" tile
    But I should not see the contextual link "Share" in the "Solution to be shared" tile

    Given I am logged in as a member of the "Collection share candidate 1" collection
    And I go to the homepage of the "Collection share original" collection
    Then I should see the "Solution to be shared" tile
    And I should see the contextual link "Share" in the "Solution to be shared" tile

    Given I am logged in as a facilitator of the "Collection share candidate 1" collection
    And I go to the homepage of the "Collection share original" collection
    Then I should see the "Solution to be shared" tile
    And I should see the contextual link "Share" in the "Solution to be shared" tile

