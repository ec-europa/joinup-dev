@api @email
Feature: Asset release moderation
  In order to manage releases
  As a user of the website
  I need to be able to transit the releases from one state to another.

  Scenario: Publish, Update, request changes, publish again and delete a release.
    Given the following owner:
      | name        | type                  |
      | Kenny Logan | Private Individual(s) |
    And the following contact:
      | name  | SheriMoore              |
      | email | SheriMoore @example.com |
    And users:
      | Username        | E-mail                      | First name | Family name | Roles     |
      | Bonnie Holloway | bonnie.holloway@example.com | Bonnie     | Holloway    |           |
      | Felix Russell   | Felix.Russell@example.com   | Felix      | Russell     |           |
      | Wilson Mendoza  | Wilson.Mendoza@example.com  | Wilson     | Mendoza     | moderator |
    And the following solution:
      | title               | Dark Ship   |
      | description         | Dark ship   |
      | logo                | logo.png    |
      | banner              | banner.jpg  |
      | owner               | Kenny Logan |
      | contact information | SheriMoore  |
      | state               | validated   |
    And the following solution user membership:
      | solution  | user            | roles       |
      | Dark Ship | Bonnie Holloway | owner       |
      | Dark Ship | Felix Russell   | facilitator |
    When I am logged in as "Bonnie Holloway"
    And I go to the homepage of the "Dark Ship" solution
    And I click "Add release" in the plus button menu
    And I fill in the following:
      | Name           | Release of the dark ship |
      | Release number | 1                        |
      | Release notes  | We go live.              |
    And I press "Save as draft"
    Then I should see the heading "Release of the dark ship 1"
    Then I should not see the following warning messages:
      | You are viewing the published version. To view the latest draft version, click here. |
    When I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Draft"
    When I fill in "Release number" with "v1"
    And I press "Publish"
    Then I should see the heading "Release of the dark ship v1"
    And I should not see the following warning messages:
      | You are viewing the published version. To view the latest draft version, click here. |
    When all e-mails have been sent
    And I click "Edit" in the "Entity actions" region
    And I fill in "Release notes" with "We go live soon."
    And I press "Update"
    Then I should see the heading "Release of the dark ship v1"
    And the following email should have been sent:
      | recipient | Felix Russell                                                                   |
      | subject   | Joinup: A release has been updated                                              |
      | body      | The release Release of the dark ship, v1 of the solution Dark Ship was updated. |
    And the following email should have been sent:
      | recipient | Wilson Mendoza                                                                  |
      | subject   | Joinup: A release has been updated                                              |
      | body      | The release Release of the dark ship, v1 of the solution Dark Ship was updated. |

    # Request changes as a moderator.
    When I am logged in as a moderator
    And all e-mails have been sent
    And I go to the "Release of the dark ship" release
    And I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Validated"
    And the following fields should be present "Motivation"
    And the following fields should not be present "Langcode, Translation"
    When I fill in "Name" with "Release"
    And I press "Request changes"
    # Motivation required.
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "I don't like it"
    And I press "Request changes"
    # The published version does not change.
    Then I should see the heading "Release of the dark ship v1"
    And I should see the following warning messages:
      | You are viewing the published version. To view the latest draft version, click here. |
    And the following email should have been sent:
      | recipient | Bonnie Holloway                                                                                                       |
      | subject   | Joinup: Modification of a release of your solution has been requested                                                 |
      | body      | the Joinup moderation team requires editing the release Release, v1 of the solution Dark Ship due to I don't like it. |
    And the following email should have been sent:
      | recipient | Felix Russell                                                                                                         |
      | subject   | Joinup: Modification of a release of your solution has been requested                                                 |
      | body      | the Joinup moderation team requires editing the release Release, v1 of the solution Dark Ship due to I don't like it. |

    # Implement changes as a facilitator.
    When I am logged in as "Bonnie Holloway"
    And I go to the "Release of the dark ship" release
    Then I should see the heading "Release of the dark ship v1"
    When I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Release Release"
    And the current workflow state should be "Needs update"
    When I fill in "Name" with "Release fix"
    And I press "Update"
    # The updated version is still not published.
    Then I should see the heading "Release of the dark ship v1"

    # Approve changes as a moderator.
    When I am logged in as a moderator
    And all e-mails have been sent
    And I go to the "Release of the dark ship" release
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    # The published is updated.
    Then I should see the heading "Release fix v1"
    And I should not see the following warning messages:
      | You are viewing the published version. To view the latest draft version, click here. |
    And the following email should have been sent:
      | recipient | Bonnie Holloway                                                                                    |
      | subject   | Joinup: Your release was accepted                                                                  |
      | body      | Your proposed Release fix, v1 for the solution "Dark Ship" has been validated as per your request. |

    # Delete a release as a moderator.
    When all e-mails have been sent
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    And the following email should have been sent:
      | recipient | Bonnie Holloway                                            |
      | subject   | Joinup: A release has been deleted                         |
      | body      | release Release fix, v1 of Dark Ship solution was deleted. |
