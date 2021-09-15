@api @group-b
Feature: Following discussions
  As a member of Joinup
  I want to follow interesting discussions
  So that I can stay up to date with its evolvement

  Background:
    Given the following collection:
      | title | Dairy products |
      | state | validated      |
    And user:
      | Username | Dr. Hans Zarkov  |
      | E-mail   | hans@example.com |
    And the following collection user membership:
      | collection     | user            |
      | Dairy products | Dr. Hans Zarkov |
    And discussion content:
      | title       | body                                                             | collection     | state     | author          |
      | Rare Butter | I think that the rarest butter out there is the milky way butter | Dairy products | validated | Dr. Hans Zarkov |
      | Rare Whey   | Whey is the liquid remaining after milk has been curdled.        | Dairy products | draft     | Dr. Hans Zarkov |
    Then the "Rare butter" discussion should have 0 subscribers

  @javascript
  Scenario: Follow a discussion.
    When I am an anonymous user
    And I go to the "Rare Butter" discussion
    Then I should not see the link "Follow"
    And I should not see the link "Unfollow"
    # The subscribe links should never be shown for a discussion which is not
    # published.
    When I go to the "Rare Whey" discussion
    Then I should not see the link "Follow"
    And I should not see the link "Unfollow"

    When I am logged in as an "authenticated user"
    # The subscribe links should never be shown for a discussion which is not
    # published.
    When I go to the "Rare Whey" discussion
    Then I should not see the link "Follow"
    And I should not see the link "Unfollow"
    And I go to the "Rare Butter" discussion
    Then I should see the link "Follow"
    And I should not see the link "Unfollow"

    When I click "Follow"
    Then I should see the link "Unfollow"
    And I should not see the link "Follow"
    And the "Rare butter" discussion should have 1 subscriber

    When I click "Unfollow"
    Then a modal should open
    And I should see "Stop following this discussion?" in the "Modal title" region
    When I press "Unfollow" in the "Modal buttons" region
    Then I should see the heading "Rare Butter"
    And I should see the link "Follow"
    And the "Rare butter" discussion should have 0 subscribers

  Scenario: Receive E-mail notifications when actions are taken in discussions.
    Given users:
      | Username    | E-mail            | First name | Family name | Notification frequency |
      | follower    | dale@example.com  | Dale       | Arden       | monthly                |
      | debater     | flash@example.com | Flash      | Gordon      | daily                  |
      | facilitator | ming@example.com  | Ming       | Merciless   | weekly                 |
    And the following collection user membership:
      | collection     | user        | roles       |
      | Dairy products | facilitator | facilitator |
    And the following discussion subscriptions:
      | username | title       |
      | follower | Rare Butter |

    # Authenticated users comments are sent on comment creation.
    Given I am logged in as debater
    And I go to the "Rare Butter" discussion
    # User 'debater' is also subscribing. We check later if, as being the author
    # of the comment, he will not receive notification.
    And I click "Follow"
    And I fill in "Create comment" with "I'm the moderator of this discussion. Let's talk."
    But I wait for the spam protection time limit to pass
    Then I press "Post comment"
    # Subscribers are receiving the notifications.
    And the following email should have been sent:
      | recipient_mail | dale@example.com                                                                              |
      | subject        | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | body           | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |
    # The user 'debater' is also a discussion subscriber but because he's the
    # author of the comment, he will not receive the notification.
    And the following email should not have been sent:
      | recipient_mail | flash@example.com                                                                             |
      | subject        | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | body           | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |
    # Discussion author is receiving the notifications too.
    And the following email should have been sent:
      | recipient_mail | hans@example.com                                                                              |
      | subject        | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | body           | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |

    # No E-mail notification is sent when the discussion is updated but no
    # relevant fields are changed.
    And I mark all emails as read
    And I am logged in as "Dr. Hans Zarkov"
    And I go to the edit form of the "Rare Butter" discussion
    And I press "Update"
    Then 0 e-mails should have been sent

    # When relevant fields of a discussion are changed, the subscribers are
    # receiving a notification.
    Given I go to the edit form of the "Rare Butter" discussion
    And I fill in "Content" with "The old content was wrong."
    And I press "Update"
    Then the following email should have been sent:
      | recipient_mail | dale@example.com                                                                  |
      | subject        | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | body           | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    And the following email should have been sent:
      | recipient_mail | flash@example.com                                                                 |
      | subject        | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | body           | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    # The author of the discussion update doesn't receive any notification.
    But the following email should not have been sent:
      | recipient_mail | hans@example.com                                                                  |
      | subject        | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | body           | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    # Check that no other mails have been unexpectedly sent.
    Then 2 e-mails should have been sent

    # If the discussion is moved from 'validated' to any other state, no
    # notification will be sent, regardless if a relevant field is changed.
    Given I mark all emails as read
    And I am logged in as a moderator
    When I go to the edit form of the "Rare Butter" discussion
    And I fill in "Content" with "Is this change triggering notifications?"
    And I fill in "Motivation" with "Reporting this content..."
    And I press "Report"
    # The notification that a moderator requests a modification should still be
    # sent to the content author.
    But the following email should have been sent:
      | recipient_mail | hans@example.com                                                          |
      | subject        | Joinup: Content has been updated                                          |
      | body           | the Moderator, has requested you to modify the discussion - "Rare Butter" |
    And 1 e-mail should have been sent

    When I go to the "Rare Butter" discussion
    And I click "Delete" in the "Entity actions" region
    And I press "Delete"

    Then the following email should have been sent:
      | recipient_mail | dale@example.com                                                                                     |
      | subject        | Joinup: The discussion "Rare Butter" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare Butter" was deleted from the "Dairy products" collection. |
    And the following email should have been sent:
      | recipient_mail | flash@example.com                                                                                    |
      | subject        | Joinup: The discussion "Rare Butter" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare Butter" was deleted from the "Dairy products" collection. |
    And the following email should have been sent:
      | recipient_mail | hans@example.com                                                                                     |
      | subject        | Joinup: The discussion "Rare Butter" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare Butter" was deleted from the "Dairy products" collection. |
    And the following email should not have been sent:
      | recipient_mail | ming@example.com                                                                                     |
      | subject        | Joinup: The discussion "Rare Butter" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare Butter" was deleted from the "Dairy products" collection. |

    # Now check the notifications sent for a published discussion.
    Given discussion content:
      | title     | body                                                   | collection     | state     | author          |
      | Rare feta | Made from milk from the exclusive Manx Loaghtan sheep. | Dairy products | validated | Dr. Hans Zarkov |
    And discussion subscriptions:
      | username    | title     |
      | follower    | Rare feta |
      | facilitator | Rare feta |
    And I am logged in as facilitator

    When I go to the "Rare feta" discussion
    And I click "Delete" in the "Entity actions" region
    And I press "Delete"

    Then the following email should have been sent:
      | recipient_mail | dale@example.com                                                                                   |
      | subject        | Joinup: The discussion "Rare feta" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare feta" was deleted from the "Dairy products" collection. |
    # Discussion author is receiving the notifications too.
    And the following email should have been sent:
      | recipient_mail | hans@example.com                                                                                   |
      | subject        | Joinup: The discussion "Rare feta" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare feta" was deleted from the "Dairy products" collection. |
    # The user 'facilitator' is also a discussion subscriber but because she's
    # the person who has deleted the comment, she will not receive the
    # notification.
    But the following email should not have been sent:
      | recipient_mail | ming@example.com                                                                                   |
      | subject        | Joinup: The discussion "Rare feta" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare feta" was deleted from the "Dairy products" collection. |
    # Flash Gordon is not subscribed. He should not retrieve the message.
    And the following email should not have been sent:
      | recipient_mail | flash@example.com                                                                                  |
      | subject        | Joinup: The discussion "Rare feta" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare feta" was deleted from the "Dairy products" collection. |
