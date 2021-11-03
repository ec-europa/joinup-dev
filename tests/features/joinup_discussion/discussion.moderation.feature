@api @group-c
Feature: Discussion moderation
  In order to manage discussions
  As a user of the website
  I need to be able to transit the discussions from one state to another.

  @terms
  Scenario: Publish, request changes, propose, publish again and archive a discussion.
    Given users:
      | Username        |
      | Gabe Rogers     |
      | Brigham Salvage |
    And the following collection:
      | title            | DIY collection                           |
      | description      | Collection of "Do it yourself" projects. |
      | logo             | logo.png                                 |
      | content creation | members                                  |
      | state            | validated                                |
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
    And I select "EU and European Policies" from "Topic"
    And I press "Publish"
    Then I should see the heading "Best method to cut logs"
    And I should see the link "Edit" in the "Entity actions" region

    # Mark the discussion as "Needs update" after a report.
    When I am logged in as a moderator
    And I go to the "Best method to cut logs" discussion
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Discussion Best method to cut logs"
    And the following fields should be present "Motivation"
    And the current workflow state should be "Published"
    When I fill in "Motivation" with "Let's test reporting"
    And I press "Report"
    # The published version does not change.
    Then I should see the heading "Best method to cut logs"

    # The owner can propose changes.
    When I am logged in as "Gabe Rogers"
    And I go to the "Best method to cut logs" discussion
    And I should see the link "Edit" in the "Entity actions" region
    And I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Needs update"
    When I fill in "Title" with "Best method to cut wood logs"
    And I press "Propose"
    # The published version does not change.
    Then I should see the heading "Best method to cut logs"

    # The owner is allowed to edit the discussion again.
    When I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Discussion Best method to cut wood logs"
    When I fill in "Title" with "Best method to cut Eucalyptus wood logs"
    And I press "Update"
    # The published version does not change.
    Then I should see the heading "Best method to cut logs"

    # Approve changes as a moderator.
    When I am logged in as a moderator
    And I go to the "Best method to cut logs" discussion
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Discussion Best method to cut Eucalyptus wood logs"
    And I press "Publish"
    # The published is updated.
    Then I should see the heading "Best method to cut Eucalyptus wood logs"

    # Disable the discussion as facilitator.
    When I am logged in as "Brigham Salvage"
    And I go to the "Best method to cut Eucalyptus wood logs" discussion
    And I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Published"
    When I press "Disable"
    # The discussion is kept published.
    Then I should see the heading "Best method to cut Eucalyptus wood logs"
    # But no further changes can be done.
    But I should not see the link "Edit" in the "Entity actions" region
    # Also the owner cannot do changes anymore.
    When I am logged in as "Gabe Rogers"
    And I go to the "Best method to cut Eucalyptus wood logs" discussion
    #Then I should not see the link "Edit" in the "Entity actions" region
    Then I should not see the link "Edit" in the "Entity actions" region

  Scenario: Disabling a discussion prevents additional comments to be created.
    Given users:
      | Username      | E-mail                    |
      | Vince Rome    | vince.rome@example.com    |
      | Lance Rustici | lance.rustici@example.com |
      | Denny Winslow | denny.winslow@example.com |
    And the following collection:
      | title            | Valentine's day survival kit                   |
      | description      | How to survive the most scary day of the year. |
      | logo             | logo.png                                       |
      | content creation | members                                        |
      | state            | validated                                      |
    And the following collection user membership:
      | collection                   | user          | roles       |
      | Valentine's day survival kit | Vince Rome    | member      |
      | Valentine's day survival kit | Lance Rustici | facilitator |
    And discussion content:
      | title                        | content                    | author     | state     | collection                   |
      | What's the best escape gift? | Buying chocolate is risky. | Vince Rome | validated | Valentine's day survival kit |
    And comments:
      | message                   | author        | mail                 | name       | parent                       |
      | Do not buy rings.         | Lance Rustici |                      |            | What's the best escape gift? |
      | What about a trip abroad? |               | anon@bestadvices.com | Anon buddy | What's the best escape gift? |

    # The comment form is available, even for non-members.
    When I am logged in as "Denny Winslow"
    And I go to the "What's the best escape gift?" discussion
    Then I should see the heading "What's the best escape gift?"
    And I should see the button "Post comment"

    # Disable the discussion with the facilitator.
    When I am logged in as "Lance Rustici"
    And I go to the "What's the best escape gift?" discussion
    And I click "Edit" in the "Entity actions" region
    And I press "Disable"
    Then I should see the message "Discussion What's the best escape gift? has been updated"

    # The comments should still be visible.
    And I should see the text "Do not buy rings."
    And I should see the text "What about a trip abroad?"
    # But not the form.
    But I should not see the button "Post comment"

    # Comments are closed for the discussion author too.
    When I am logged in as "Vince Rome"
    And I go to the "What's the best escape gift?" discussion
    Then I should see the text "Do not buy rings."
    And I should see the text "What about a trip abroad?"
    But I should not see the button "Post comment"
