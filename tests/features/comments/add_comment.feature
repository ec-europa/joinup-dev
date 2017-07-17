@api
Feature: Add comments
  As a visitor of the website I can leave a comment on community content.

  Background:
    Given the following collection:
      | title | Gossip collection |
      | logo  | logo.png          |
      | state | validated         |

    And users:
      | Username        | E-mail                 |
      | Miss tell tales | tell.tales@example.com |

  @javascript
  Scenario Outline: Make an anonymous comment, needs moderation.
    Given <content type> content:
      | title   | body                                                | collection        | state   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <state> |
    Given I am an anonymous user
    When I go to the content page of the type "<content type>" with the title "<title>"
    # Anonymous users do not have a rich text editor.
    Then the "Create comment" field should not have a wysiwyg editor
    When I fill in "Your name" with "Mr Scandal"
    And I fill in "Email" with "mrscandal@example.com"
    And I fill in "Create comment" with "I've heard this story..."
    Then I press "Post comment"
    Then I should see the following success messages:
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And I should not see "I've heard this story..."

    # Users with 'administer comments' permission can see the comment that is set for approval.
    Given I am logged in as a facilitator of the "Gossip collection" collection
    When I go to the content page of the type "<content type>" with the title "<title>"
    Then I should see "I've heard this story..."

    # The configuration options for comments should not be shown to
    # facilitators. Whether or not comments are available is managed on
    # collection level.
    When I click "Edit" in the "Entity actions" region
    Then I should not see the text "Comment settings"

    Examples:
      | content type | title               | state     |
      | news         | Scandalous news     | validated |
      | event        | Celebrity gathering | validated |
      | discussion   | Is gossip bad?      | validated |
      | document     | Wikileaks           | validated |

  @javascript
  Scenario Outline: Make an authenticated comment, skips moderation.
    Given <content type> content:
      | title   | body                                                | collection        | state   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <state> |
    Given I am logged in as "Miss tell tales"
    When I go to the content page of the type "<content type>" with the title "<title>"
    # Authenticated users can use a rich text editor to enter comments.
    Then I should see the "Create comment" wysiwyg editor
    # @todo A bug is causing the CKEditor JS to not attach properly (?) unless
    # the page is refreshed.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3612
    When I reload the page
    When I enter "Mr scandal was doing something weird the other day." in the "Create comment" wysiwyg editor
    And I press "Post comment"
    Then I should not see the following success messages:
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And I should see text matching "Mr scandal was doing something weird the other day."

    Examples:
      | content type | title               | state     |
      | news         | Scandalous news     | validated |
      | event        | Celebrity gathering | validated |
      | discussion   | Is gossip bad?      | validated |
      | document     | Wikileaks           | validated |
