@api @email
Feature: Add comments
  As a visitor of the website I can leave a comment on community content.

  Background:
    Given the following collections:
      | title             | state     | closed |
      | Gossip collection | validated | no     |
      | Shy collection    | validated | yes    |
    And users:
      | Username          | E-mail                        | Roles     | First name | Family name |
      | Miss tell tales   | tell.tales@example.com        |           | Miss       | Tales       |
      | Comment moderator | comment.moderator@example.com | moderator | Comment    | Moderator   |

  @javascript
  Scenario Outline: Make an anonymous comment, needs moderation.
    Given <content type> content:
      | title   | body                                                | collection        | state   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <state> |
    Given I am an anonymous user
    And all e-mails have been sent
    When I go to the content page of the type "<content type>" with the title "<title>"
    # Anonymous users do not have a rich text editor.
    Then the "Create comment" field should not have a wysiwyg editor
    # The honeypot field that needs to be empty on submission.
    And the following fields should be present "user_homepage"
    When I fill in "Your name" with "Mr Scandal"
    And I fill in "Email" with "mrscandal@example.com"
    And I fill in "Create comment" with "I've heard this story..."
    And I wait for the honeypot validation to pass
    Then I press "Post comment"
    Then I should see the following success messages:
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And I should not see "I've heard this story..."
    And the email sent to "Comment moderator" with subject "Joinup: A new comment has been created." contains the following lines of text:
      | text                                                                                    |
      | an anonymous user posted a comment in collection "Gossip collection".                   |
      | To view the comment click                                                               |
      | If you think this action is not clear or not due, please contact Joinup Support at http |

    # Users with 'administer comments' permission can see the comment that is set for approval.
    Given I am logged in as a facilitator of the "Gossip collection" collection
    When I go to the content page of the type "<content type>" with the title "<title>"
    Then I should see "I've heard this story..."

    # The configuration options for comments should not be shown to
    # facilitators. Whether or not comments are available is managed on
    # collection level.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then I should not see the text "Comment settings"

    Examples:
      | content type | title               | state     |
      | news         | Scandalous news     | validated |
      | event        | Celebrity gathering | validated |
      | discussion   | Is gossip bad?      | validated |
      | document     | Wikileaks           | validated |

  # This scenario uses javascript to work as regression test for a bug that
  # makes CKEditor unusable upon a page load.
  # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3612
  @javascript
  Scenario Outline: Make an authenticated comment, skips moderation.
    Given <content type> content:
      | title   | body                                                | collection        | state   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <state> |
    Given I am logged in as "Miss tell tales"
    And all e-mails have been sent
    When I go to the content page of the type "<content type>" with the title "<title>"
    # The honeypot field that needs to be empty on submission.
    Then the following fields should be present "user_homepage"
    # Authenticated users can use a rich text editor to enter comments.
    And I should see the "Create comment" wysiwyg editor
    When I enter "Mr scandal was doing something weird the other day." in the "Create comment" wysiwyg editor
    And I wait for the honeypot validation to pass
    And I press "Post comment"
    Then I should not see the following success messages:
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And the page should contain the html text "Mr scandal was doing something weird the other day."
    # The author's full name should be shown, not the username.
    And I should see the link "Miss Tales"
    But I should not see the link "Miss tell tales"
    And the email sent to "Comment moderator" with subject "Joinup: A new comment has been created." contains the following lines of text:
      | text                                                                                    |
      | Miss Tales posted a comment in collection "Gossip collection".                          |
      | To view the comment click                                                               |
      | If you think this action is not clear or not due, please contact Joinup Support at http |

    Examples:
      | content type | title               | state     |
      | news         | Scandalous news     | validated |
      | event        | Celebrity gathering | validated |
      | discussion   | Is gossip bad?      | validated |
      | document     | Wikileaks           | validated |

  Scenario Outline: Authenticated users can insert <p> and <br> tags in the comment body.
    Given <content type> content:
      | title   | body                                                | collection        | state   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <state> |
    Given I am logged in as "Miss tell tales"
    And all e-mails have been sent
    When I go to the content page of the type "<content type>" with the title "<title>"
    And I fill in "Create comment" with "<p>Mr scandal was doing something<br />weird the other day.<p/>"
    And I wait for the honeypot validation to pass
    Then I press "Post comment"
    Then I should not see the following success messages:
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And the page should contain the html text "<p>Mr scandal was doing something<br>weird the other day.</p>"

    Examples:
      | content type | title               | state     |
      | news         | Scandalous news     | validated |
      | event        | Celebrity gathering | validated |
      | discussion   | Is gossip bad?      | validated |
      | document     | Wikileaks           | validated |

  Scenario Outline: Comments are disallowed for anonymous users in closed collections.
    Given <content type> content:
      | title   | body                                                | collection     | state   |
      | <title> | How could this ever happen? Moral panic on its way! | Shy collection | <state> |

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
      | content type | title               | state     |
      | news         | Scandalous news     | validated |
      | event        | Celebrity gathering | validated |
      | discussion   | Is gossip bad?      | validated |
      | document     | Wikileaks           | validated |
