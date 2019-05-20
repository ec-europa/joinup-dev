@api
Feature: Subscribing to discussions
  As a member of Joinup
  I want to subscribe to interesting discussions
  So that I can stay up to date with its evolvements.

  Background:
    Given the following collection:
      | title | Dairy products |
      | state | validated      |
    And user:
      | Username | Dr. Hans Zarkov  |
      | E-mail   | hans@example.com |
    And discussion content:
      | title       | body                                                             | collection     | state     | author          |
      | Rare Butter | I think that the rarest butter out there is the milky way butter | Dairy products | validated | Dr. Hans Zarkov |
      | Rare Whey   | Whey is the liquid remaining after milk has been curdled.        | Dairy products | draft     | Dr. Hans Zarkov |
    Then the "Rare butter" discussion should have 0 subscribers

  @javascript
  Scenario: Subscribe to a discussion.
    When I am an anonymous user
    And I go to the "Rare Butter" discussion
    Then I should not see the link "Subscribe"
    And I should not see the link "Unsubscribe"
    # The subscribe links should never be shown for a discussion which is not
    # published.
    When I go to the "Rare Whey" discussion
    Then I should not see the link "Subscribe"
    And I should not see the link "Unsubscribe"

    When I am logged in as an "authenticated user"
    # The subscribe links should never be shown for a discussion which is not
    # published.
    When I go to the "Rare Whey" discussion
    Then I should not see the link "Subscribe"
    And I should not see the link "Unsubscribe"
    And I go to the "Rare Butter" discussion
    Then I should see the link "Subscribe"
    And I should not see the link "Unsubscribe"

    When I click "Subscribe"
    Then I should see the link "Unsubscribe"
    And I should not see the link "Subscribe"
    And the "Rare butter" discussion should have 1 subscriber

    When I click "Unsubscribe"
    Then a modal should open
    And I should see "Unsubscribe from this discussion?" in the "Modal title" region
    When I press "Unsubscribe" in the "Modal buttons" region
    Then I should see the heading "Rare Butter"
    And I should see the link "Subscribe"
    And the "Rare butter" discussion should have 0 subscribers

  @email
  Scenario: Receive E-mail notifications when actions are taken in discussions.
    Given users:
      | Username    | E-mail            | First name | Family name | Notification frequency |
      | follower    | dale@example.com  | Dale       | Arden       | immediate              |
      | debater     | flash@example.com | Flash      | Gordon      | daily                  |
      | facilitator | ming@example.com  | Ming       | Merciless   | weekly                 |
    And the following collection user membership:
      | collection     | user        | roles       |
      | Dairy products | facilitator | facilitator |
    And the following discussion content subscriptions:
      | username | title       |
      | follower | Rare Butter |

    # Authenticated users comments are sent on comment creation.
    Given I am logged in as debater
    And I go to the "Rare Butter" discussion
    # User 'debater' is also subscribing. We check later if, as being the author
    # of the comment, he will not receive notification.
    And I click "Subscribe"
    And I fill in "Create comment" with "I'm the moderator of this discussion. Let's talk."
    But I wait for the honeypot validation to pass
    Then I press "Post comment"
    # Subscribers are receiving the notifications.
    And the following email should have been sent:
      | recipient_mail | dale@example.com                                                                              |
      | subject        | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | body           | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |
    # The user 'debater' is also a discussion subscriber but because he's the
    # author of the comment, he will not receive the notification.
    And the daily digest for "debater" should not contain the following message for the "Rare Butter" node:
      | mail_subject | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | mail_body    | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |
    # Discussion author is receiving the notifications too.
    And the daily digest for "Dr. Hans Zarkov" should contain the following message for the "Rare Butter" node:
      | mail_subject | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | mail_body    | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |

    # No E-mail notification is sent when the discussion is updated but no
    # relevant fields are changed.
    Given all message digests have been delivered
    And the mail collector cache is empty
    And I am logged in as "Dr. Hans Zarkov"
    When I go to the discussion content "Rare Butter" edit screen
    And I press "Update"
    Then 0 e-mails should have been sent

    # When relevant fields of a discussion are changed, the subscribers are
    # receiving a notifications.
    Given I go to the discussion content "Rare Butter" edit screen
    And I fill in "Content" with "The old content was wrong."
    And I press "Update"
    And the following email should have been sent:
      | recipient_mail | dale@example.com                                                                  |
      | subject        | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | body           | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    And the daily digest for "debater" should contain the following message for the "Rare Butter" node:
      | mail_subject | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | mail_body    | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    # Let's also check that the actual digest mail is successfully delivered.
    When all message digests have been delivered
    # @todo Send the mail as HTML and provide a signature.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4254
    Then the following email should have been sent:
      | recipient_mail     | flash@example.com                                                            |
      | subject            | Rare Butter message digest                                                   |
      | body               | The discussion "Rare Butter" was updated in the "Dairy products" collection. |
      | html               | no                                                                           |
      | signature_required | no                                                                           |
    # The author of the discussion update doesn't receive any notification.
    But the daily digest for "Dr. Hans Zarkov" should not contain the following message for the "Rare Butter" node:
      | mail_subject | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | mail_body    | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    Then 2 e-mails should have been sent

    # If the discussion is moved from 'validated' to any other state, no
    # notification will be send, regardless if a relevant field is changed.
    Given the mail collector cache is empty
    And I am logged in as a moderator
    When I go to the discussion content "Rare Butter" edit screen
    And I fill in "Content" with "Is this change triggering notifications?"
    And I fill in "Motivation" with "Reporting this content..."
    And I press "Report"
    Then the following email should not have been sent:
      | recipient_mail | dale@example.com                                                                  |
      | subject        | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | body           | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    And the daily digest for "debater" should not contain the following message for the "Rare Butter" node:
      | mail_subject | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | mail_body    | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |

    # Delete the discussion and check that no notifications are sent. Since the
    # discussion is not published nobody should be notified.
    When I go to the "Rare butter" discussion
    And I click "Delete" in the "Entity actions" region
    And I press "Delete"

    Then the following email should not have been sent:
      | recipient_mail | dale@example.com                                                                                     |
      | subject        | Joinup: The discussion "Rare butter" was deleted in the space of "Dairy products"                    |
      | body           | for your information, the discussion "Rare butter" was deleted from the "Dairy products" collection. |
    And the daily digest for "debater" should not contain the following message:
      | mail_subject | Joinup: The discussion "Rare butter" was deleted in the space of "Dairy products"                    |
      | mail_body    | for your information, the discussion "Rare butter" was deleted from the "Dairy products" collection. |
    And the daily digest for "Dr. Hans Zarkov" should not contain the following message:
      | mail_subject | Joinup: The discussion "Rare butter" was deleted in the space of "Dairy products"                    |
      | mail_body    | for your information, the discussion "Rare butter" was deleted from the "Dairy products" collection. |

    # Now try to delete a published discussion. The notifications should be sent
    # in this case.
    Given discussion content:
      | title     | body                                                   | collection     | state     | author          |
      | Rare feta | Made from milk from the exclusive Manx Loaghtan sheep. | Dairy products | validated | Dr. Hans Zarkov |
    And discussion content subscriptions:
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
    And the daily digest for "Dr. Hans Zarkov" should contain the following message:
      | mail_subject | Joinup: The discussion "Rare feta" was deleted in the space of "Dairy products"                    |
      | mail_body    | for your information, the discussion "Rare feta" was deleted from the "Dairy products" collection. |
    # The user 'facilitator' is also a discussion subscriber but because she's
    # the person who has deleted the comment, she will not receive the
    # notification.
    But the weekly digest for "facilitator" should not contain the following message:
      | mail_subject | Joinup: The discussion "Rare feta" was deleted in the space of "Dairy products"                    |
      | mail_body    | for your information, the discussion "Rare feta" was deleted from the "Dairy products" collection. |
    # Flash Gordon is not subscribed. He should not retrieve the message.
    And the daily digest for "debater" should not contain the following message:
      | mail_subject | Joinup: The discussion "Rare feta" was deleted in the space of "Dairy products"                    |
      | mail_body    | for your information, the discussion "Rare feta" was deleted from the "Dairy products" collection. |
