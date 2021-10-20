@api @group-e
Feature: Add comments
  As a visitor of the website I can leave a comment on community content.

  Background:
    Given the following collections:
      | title             | state     | closed |
      | Gossip collection | validated | no     |
    And users:
      | Username        | E-mail                 | Roles | First name | Family name |
      | Miss tell tales | tell.tales@example.com |       | Miss       | Tales       |

  # This scenario uses javascript to work as regression test for a bug that
  # makes CKEditor unusable upon a page load.
  # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3612
  @javascript
  Scenario Outline: Make an authenticated comment, skips moderation.

    Given solutions:
      | title                | collection        | state     |
      | Gossip girl solution | Gossip collection | validated |
    And users:
      | Username          | E-mail                        | Roles     | First name | Family name |
      | Comment moderator | comment.moderator@example.com | moderator | Comment    | Moderator   |
      | Layonel Sarok     | layonel.sarok@example.com     |           | Layonel    | Sarok       |
      | Korma Salya       | korma.salya@example.com       |           | Korma      | Salya       |
      | Salma Coster      | salma.coster@example.com      |           | Salma      | Coster      |
    And the following collection user memberships:
      | collection        | user          | roles                      |
      | Gossip collection | Layonel Sarok | administrator, facilitator |
      | Gossip collection | Korma Salya   | facilitator                |
    And the following solution user memberships:
      | solution             | user          | roles                      |
      | Gossip girl solution | Layonel Sarok | administrator, facilitator |
      | Gossip girl solution | Korma Salya   | facilitator                |
    And <content type> content:
      | title   | body                                                | <parent>       | state   | author       |
      | <title> | How could this ever happen? Moral panic on its way! | <parent title> | <state> | Salma Coster |
    Given I am logged in as "Miss tell tales"
    When I go to the content page of the type "<content type>" with the title "<title>"
    # The honeypot field that needs to be empty on submission.
    Then the following fields should be present "user_homepage"
    # Authenticated users can use a rich text editor to enter comments.
    And I should see the "Create comment" wysiwyg editor
    And the "Image" button should be available in the "Create comment" wysiwyg editor
    When I enter "Mr scandal was doing something weird the other day." in the "Create comment" wysiwyg editor
    And I wait for the spam protection time limit to pass
    And I press "Post comment"
    Then I should not see the following success messages:
      | success messages                                                                                     |
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And the page should contain the html text "Mr scandal was doing something weird the other day."
    # The author's full name should be shown, not the username.
    And I should see the link "Miss Tales"
    But I should not see the link "Miss tell tales"
    And the email sent to "Comment moderator" with subject "Joinup: A new comment has been created." contains the following lines of text:
      | text                                                                                    |
      | Miss Tales posted a comment in <parent> "<parent title>".                               |
      | To view the comment click                                                               |
      | If you think this action is not clear or not due, please contact Joinup Support at http |
    And the email sent to "Layonel Sarok" with subject "Joinup: A new comment has been created." contains the following lines of text:
      | text                                                                                    |
      | Miss Tales posted a comment in <parent> "<parent title>".                               |
      | To view the comment click                                                               |
      | If you think this action is not clear or not due, please contact Joinup Support at http |
    And the email sent to "Korma Salya" with subject "Joinup: A new comment has been created." contains the following lines of text:
      | text                                                                                    |
      | Miss Tales posted a comment in <parent> "<parent title>".                               |
      | To view the comment click                                                               |
      | If you think this action is not clear or not due, please contact Joinup Support at http |
    # Owner receives an email.
    And the email sent to "Salma Coster" with subject "Joinup: A new comment has been created." contains the following lines of text:
      | text                                                                                    |
      | Miss Tales posted a comment in <parent> "<parent title>".                               |
      | To view the comment click                                                               |
      | If you think this action is not clear or not due, please contact Joinup Support at http |

    # Verify the anchored link works properly.
    When I am logged in as "Comment moderator"
    And I click the comment link from the last email sent to "Comment moderator"
    Then I should see the heading "<title>"
    And I should see the text "Mr scandal was doing something weird the other day."

    When I am logged in as "Salma Coster"
    And I click the comment link from the last email sent to "Salma Coster"
    And I click "Reply"
    And I should see the "Create comment" wysiwyg editor
    When I enter "I am replying in user's comment." in the "Create comment" wysiwyg editor
    And I wait for the spam protection time limit to pass
    And I press "Post comment"
    # Parent comment owner receives an email.
    And the email sent to "Miss tell tales" with subject "Joinup: A new comment has been created." contains the following lines of text:
      | text                                                                                    |
      | Salma Coster posted a comment in <parent> "<parent title>".                             |
      | To view the comment click                                                               |
      | If you think this action is not clear or not due, please contact Joinup Support at http |
    # The owner of the content does not receive a notification due to being the actor.
    And the following email should not have been sent:
      | recipient | Salma Coster                            |
      | subject   | Joinup: A new comment has been created. |
      | body      | Salma Coster posted a comment in        |

    Examples:
      | content type | title               | state     | parent     | parent title         |
      | news         | Scandalous news     | validated | collection | Gossip collection    |
      | event        | Celebrity gathering | validated | collection | Gossip collection    |
      | discussion   | Is gossip bad?      | validated | collection | Gossip collection    |
      | document     | Wikileaks           | validated | collection | Gossip collection    |
      # Add an example also for solutions to ensure the variables are properly replaced.
      | news         | Scandalous news     | validated | solution   | Gossip girl solution |

  Scenario Outline: Posting comments.
    Given <content type> content:
      | title   | body                                                | collection        | state   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <state> |
    Given I am logged in as "Miss tell tales"
    When I go to the content page of the type "<content type>" with the title "<title>"
    # Authenticated users can insert <p> and <br> tags in the comment body.
    And I fill in "Create comment" with "<p>Mr scandal was doing something<br />weird the other day.<p/>"
    And I wait for the spam protection time limit to pass
    Then I press "Post comment"
    Then I should not see the following success messages:
      | success messages                                                                                     |
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And comment #1 should contain the markup "<p>Mr scandal was doing something<br>weird the other day.</p>"
    And comment #1 indent is 0

    When I click "Reply" in comment #1
    And I fill in "Create comment" with "Comment indent 1"
    And I wait for the spam protection time limit to pass
    When I press "Post comment"
    Then comment #2 should contain the markup "Comment indent 1"
    And comment #2 indent is 1

    When I click "Reply" in comment #2
    And I fill in "Create comment" with "Comment indent 2"
    And I wait for the spam protection time limit to pass
    When I press "Post comment"
    # This comment hit the maximum deep.
    Then comment #3 should contain the markup "Comment indent 2"
    And comment #3 indent is 2

    # Reply to the deepest comment.
    When I click "Reply" in comment #3
    And I fill in "Create comment" with "Reply to #3"
    And I wait for the spam protection time limit to pass
    When I press "Post comment"
    Then comment #4 should contain the markup "Reply to #3"
    # The reply has the same indent as the parent comment.
    And comment #4 indent is 2

    # Reply to reply.
    When I click "Reply" in comment #4
    And I fill in "Create comment" with "Reply to reply"
    And I wait for the spam protection time limit to pass
    When I press "Post comment"
    Then comment #5 should contain the markup "Reply to reply"
    # The reply to reply has the same indent as the deepest comment.
    And comment #5 indent is 2

    Given I am an anonymous user
    When I go to the content page of the type "<content type>" with the title "<title>"
    # For anonymous users we display this message at the bottom of comment list.
    Then I should see the text "Login or create an account to comment."
    # But we don't show the Drupal core login/register links on each comment.
    But I should not see the text "Log in or register to post comments"

    Examples:
      | content type | title               | state     |
      | news         | Scandalous news     | validated |
      | event        | Celebrity gathering | validated |
      | discussion   | Is gossip bad?      | validated |
      | document     | Wikileaks           | validated |

  Scenario Outline: Comments are disallowed for anonymous users.

    Given the following collections:
      | title          | state     | closed |
      | Shy collection | validated | yes    |
    And <content type> content:
      | title   | body                                                | collection   | state   |
      | <title> | How could this ever happen? Moral panic on its way! | <collection> | <state> |

    # Anonymous users should not be able to comment.
    Given I am an anonymous user
    When I go to the content page of the type "<content type>" with the title "<title>"
    Then I should see the text "Login or create an account to comment"
    And the link "Login" should point to "caslogin"
    And the link "create an account" should point to "user/register"
    And the following fields should not be present "Create comment"
    And I should not see the button "Post comment"

    # Logged-in users can still comment.
    Given I am logged in as "Miss tell tales"
    When I go to the content page of the type "<content type>" with the title "<title>"
    Then the following fields should be present "Create comment"
    And I should see the button "Post comment"

    Examples:
      | collection        | content type | title                     | state     |
      | Shy collection    | news         | Scandalous news           | validated |
      | Shy collection    | event        | Celebrity gathering       | validated |
      | Shy collection    | discussion   | Is gossip bad?            | validated |
      | Shy collection    | document     | Wikileaks                 | validated |
      | Gossip collection | news         | Rihanna wears pope outfit | validated |
      | Gossip collection | event        | Taylor Swift wedding      | validated |
      | Gossip collection | discussion   | Is gossip good?           | validated |
      | Gossip collection | document     | Celebrity scandals 2019   | validated |
