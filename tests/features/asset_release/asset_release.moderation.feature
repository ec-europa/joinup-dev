@api
Feature: Asset release moderation
  In order to manage releases
  As a user of the website
  I need to be able to transit the releases from one state to another.

  Scenario: Publish, request changes and publish again a release.
    Given the following owner:
      | name        | type                  |
      | Kenny Logan | Private Individual(s) |
    And the following contact:
      | name  | SheriMoore              |
      | email | SheriMoore @example.com |
    And user:
      | Username | Bonnie Holloway |
    And the following solution:
      | title               | Dark Ship   |
      | description         | Dark ship   |
      | logo                | logo.png    |
      | banner              | banner.jpg  |
      | owner               | Kenny Logan |
      | contact information | SheriMoore  |
      | state               | validated   |
    And the following solution user membership:
      | solution  | user            | roles |
      | Dark Ship | Bonnie Holloway | owner |
    When I am logged in as "Bonnie Holloway"
    And I go to the homepage of the "Dark Ship" solution
    And I click "Add release" in the plus button menu
    And I fill in the following:
      | Name           | Release of the dark ship |
      | Release number | 1                        |
      | Release notes  | We go live.              |
    And I press "Save as draft"
    Then I should see the heading "Release of the dark ship 1"
    When I click "Edit" in the "Entity actions" region
    And I fill in "Release number" with "v1"
    And I press "Publish"
    Then I should see the heading "Release of the dark ship v1"

    # Request changes as a moderator.
    When I am logged in as a moderator
    And I go to the "Release of the dark ship" release
    And I click "Edit" in the "Entity actions" region
    And I fill in "Name" with "Release"
    And I press "Request changes"
    # The published version does not change.
    Then I should see the heading "Release of the dark ship v1"

    # Implement changes as a facilitator.
    When I am logged in as "Bonnie Holloway"
    And I go to the "Release of the dark ship" release
    Then I should see the heading "Release of the dark ship v1"
    When I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Release Release"
    When I fill in "Name" with "Release fix"
    And I press "Update"
    # The updated version is still not published.
    Then I should see the heading "Release of the dark ship v1"

    # Approve changes as a moderator.
    When I am logged in as a moderator
    And I go to the "Release of the dark ship" release
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    # The published is updated.
    Then I should see the heading "Release fix v1"
