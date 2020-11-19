@api @email @group-a
Feature: Add comments
  As a visitor of the website I can leave a comment on community content.

  Background:
    Given the following collections:
      | title             | state     | closed |
      | Gossip collection | validated | no     |
      | Shy collection    | validated | yes    |
    And solutions:
      | title                | collection        | state     |
      | Gossip girl solution | Gossip collection | validated |
    And users:
      | Username          | E-mail                        | Roles     | First name | Family name |
      | Miss tell tales   | tell.tales@example.com        |           | Miss       | Tales       |
      | Comment moderator | comment.moderator@example.com | moderator | Comment    | Moderator   |
      | Layonel Sarok     | layonel.sarok@example.com     |           | Layonel    | Sarok       |
      | Korma Salya       | korma.salya@example.com       |           | Korma      | Salya       |
    And the following collection user memberships:
      | collection        | user          | roles                      |
      | Gossip collection | Layonel Sarok | administrator, facilitator |
      | Gossip collection | Korma Salya   | facilitator                |
    And the following solution user memberships:
      | solution             | user          | roles                      |
      | Gossip girl solution | Layonel Sarok | administrator, facilitator |
      | Gossip girl solution | Korma Salya   | facilitator                |

  # This scenario uses javascript to work as regression test for a bug that
  # makes CKEditor unusable upon a page load.
  # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3612
  @javascript
  Scenario Outline: Make an authenticated comment, skips moderation.
    Given <content type> content:
      | title   | body                                                | <parent>       | state   |
      | <title> | How could this ever happen? Moral panic on its way! | <parent title> | <state> |
    Given I am logged in as "Miss tell tales"
    And all e-mails have been sent
    When I go to the content page of the type "<content type>" with the title "<title>"
    # The honeypot field that needs to be empty on submission.
    Then the following fields should be present "user_homepage"
    # Authenticated users can use a rich text editor to enter comments.
    And I should see the "Create comment" wysiwyg editor
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

    # Verify the anchored link works properly.
    When I am logged in as "Comment moderator"
    And I click the comment link from the last email sent to "Comment moderator"
    Then I should see the heading "<title>"
    And I should see the text "Mr scandal was doing something weird the other day."
    And the page should point to the anchor from the URL

    Examples:
      | content type | title               | state     | parent     | parent title         |
      | news         | Scandalous news     | validated | collection | Gossip collection    |
      | event        | Celebrity gathering | validated | collection | Gossip collection    |
      | discussion   | Is gossip bad?      | validated | collection | Gossip collection    |
      | document     | Wikileaks           | validated | collection | Gossip collection    |
      # Add an example also for solutions to ensure the variables are properly replaced.
      | news         | Scandalous news     | validated | solution   | Gossip girl solution |


  Scenario Outline: Authenticated users can insert <p> and <br> tags in the comment body.
    Given <content type> content:
      | title   | body                                                | collection        | state   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <state> |
    Given I am logged in as "Miss tell tales"
    And all e-mails have been sent
    When I go to the content page of the type "<content type>" with the title "<title>"
    And I fill in "Create comment" with "<p>Mr scandal was doing something<br />weird the other day.<p/>"
    And I wait for the spam protection time limit to pass
    Then I press "Post comment"
    Then I should not see the following success messages:
      | success messages                                                                                     |
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And the page should contain the html text "<p>Mr scandal was doing something<br>weird the other day.</p>"

    Examples:
      | content type | title               | state     |
      | news         | Scandalous news     | validated |
      | event        | Celebrity gathering | validated |
      | discussion   | Is gossip bad?      | validated |
      | document     | Wikileaks           | validated |

  Scenario Outline: Comments are disallowed for anonymous users.
    Given <content type> content:
      | title   | body                                                | collection   | state   |
      | <title> | How could this ever happen? Moral panic on its way! | <collection> | <state> |

    # Anonymous users should not be able to comment.
    Given I am an anonymous user
    When I go to the content page of the type "<content type>" with the title "<title>"
    Then I should see the text "Login or create an account to comment"
    And the link "Login" should point to "user/login"
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
