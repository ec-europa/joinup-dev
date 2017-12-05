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

  Scenario: Subscribe to a discussion.
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
    And I should not see the link "Subscribe"

    When I click "Unsubscribe"
    Then I should see the heading "Unsubscribe from this discussion?"
    When I press "Unsubscribe"
    Then I should see the heading "Rare Butter"
    And I should see the link "Subscribe"

  @email
  Scenario: Receive an E-mail notification when a comment is added or a
    discussion is updated.

    Given users:
      | Username | E-mail            | First name | Family name |
      | follower | dale@example.com  | Dale       | Arden       |
      | debater  | flash@example.com | Flash      | Gordon      |

    # Subscribe the 'follower' user to the discussion.
    And I am logged in as follower
    And I go to the "Rare Butter" discussion
    And I click "Subscribe"

    # Notifications are only sent for anonymous users when the comment is
    # approved.
    Given I am an anonymous user
    And I go to the "Rare Butter" discussion
    Then I fill in "Create comment" with "Is Dale in love with Flash?"
    And I fill in "Your name" with "Gerhardt von Troll"
    And I fill in "Email" with "trollingismylife@example.com"
    But I wait for the honeypot validation to pass
    Then I press "Post comment"
    Then 0 e-mails should have been sent
    # Moderate the anonymous comment.
    Given I am logged in as a "moderator"
    And I go to "/admin/content/comment/approval"
    Given I select the "Rare Butter" row
    Then I select "Publish comment" from "Action"
    And I press "Apply to selected items"
    # Subscribers are receiving the notifications.
    And the following email should have been sent:
      | recipient | dale@example.com                                                                                    |
      | subject   | Joinup: User Gerhardt von Troll posted a comment in discussion "Rare Butter"                        |
      | body      | Gerhardt von Troll has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |
    # Discussion author is receiving the notifications too.
    And the following email should have been sent:
      | recipient | hans@example.com                                                                                    |
      | subject   | Joinup: User Gerhardt von Troll posted a comment in discussion "Rare Butter"                        |
      | body      | Gerhardt von Troll has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |

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
      | recipient | dale@example.com                                                                              |
      | subject   | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | body      | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |
    # The user 'debater' is also a discussion subscriber but because he's the
    # author of the comment, he will not receive the notification.
    But the following email should not have been sent:
      | recipient | flash@example.com                                                                             |
      | subject   | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | body      | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |
    # Discussion author is receiving the notifications too.
    And the following email should have been sent:
      | recipient | hans@example.com                                                                              |
      | subject   | Joinup: User Flash Gordon posted a comment in discussion "Rare Butter"                        |
      | body      | Flash Gordon has posted a comment on discussion "Rare Butter" in "Dairy products" collection. |

    # No E-mail notification is sent when the discussion is updated but no
    # relevant fields are changed.
    Given  the mail collector cache is empty
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
      | recipient | dale@example.com                                                                  |
      | subject   | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | body      | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    And the following email should have been sent:
      | recipient | flash@example.com                                                                  |
      | subject   | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | body      | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    # The author of the discussion update doesn't receive any notification.
    But the following email should have been sent:
      | recipient | flash@example.com                                                                  |
      | subject   | Joinup: The discussion "Rare Butter" was updated in the space of "Dairy products" |
      | body      | The discussion "Rare Butter" was updated in the "Dairy products" collection.      |
    Then 2 e-mails should have been sent
