@api
Feature: Discussion moderation
  In order to manage discussions
  As a user of the website
  I need to be able to transit the discussions from one state to another.

  Scenario: Publish, request changes, propose, publish again and archive a discussion.
    Given users:
      | name            |
      | Gabe Rogers     |
      | Brigham Salvage |
    And the following collection:
      | title             | DIY collection                           |
      | description       | Collection of "Do it yourself" projects. |
      | logo              | logo.png                                 |
      | elibrary creation | members                                  |
      | state             | validated                                |
    And the following collection user membership:
      | collection     | user            | roles       |
      | DIY collection | Gabe Rogers     | member      |
      | DIY collection | Brigham Salvage | facilitator |

    # A member of the collection can create a discussion.
    When I am logged in as "Gabe Rogers"
    And I go to the homepage of the "DIY collection" collection
    And I click "Add discussion" in the plus button menu
    And I fill in the following:
      | Title   | Best method to cut logs        |
      | Content | Paying somebody else to do it? |
    And I press "Publish"
    Then I should see the heading "Best method to cut logs"
    And I should see the link "Edit" in the "Entity actions" region

    # Mark the discussion as in assessment after a report.
    When I am logged in as a moderator
    And I go to the "Best method to cut logs" discussion
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Discussion Best method to cut logs"
    And I press "Report"
    # The published version does not change.
    Then I should see the heading "Best method to cut logs"

    # Further changes to the discussion are not allowed to the owner anymore.
    When I am logged in as "Gabe Rogers"
    And I go to the "Best method to cut logs" discussion
    And I should not see the link "Edit" in the "Entity actions" region

    # Approve report and ask for changes.
    When I am logged in as "Brigham Salvage"
    And I go to the "Best method to cut logs" discussion
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "Best method to cut wood logs"
    And I press "Approve report"
    # The published version does not change.
    Then I should see the heading "Best method to cut logs"

    # The owner is allowed to edit the discussion again.
    When I am logged in as "Gabe Rogers"
    And I go to the "Best method to cut logs" discussion
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Discussion Best method to cut wood logs"
    When I fill in "Title" with "Best method to cut Eucalyptus wood logs"
    And I press "Update proposed"
    # The published version does not change.
    Then I should see the heading "Best method to cut logs"

    # Approve changes as a moderator.
    When I am logged in as a moderator
    And I go to the "Best method to cut logs" discussion
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Discussion Best method to cut Eucalyptus wood logs"
    And I press "Approve proposed"
    # The published is updated.
    Then I should see the heading "Best method to cut Eucalyptus wood logs"

    # Disable the discussion as facilitator.
    When I am logged in as "Brigham Salvage"
    And I go to the "Best method to cut Eucalyptus wood logs" discussion
    And I click "Edit" in the "Entity actions" region
    And I press "Disable"
    # The discussion is kept published.
    Then I should see the heading "Best method to cut Eucalyptus wood logs"
    # But no further changes can be done.
    But I should not see the link "Edit" in the "Entity actions" region
    # Also the owner cannot do changes anymore.
    When I am logged in as "Gabe Rogers"
    And I go to the "Best method to cut Eucalyptus wood logs" discussion
    #Then I should not see the link "Edit" in the "Entity actions" region
    Then I should not see the link "Edit" in the "Entity actions" region
