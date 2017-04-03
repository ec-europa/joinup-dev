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

  Scenario Outline: Make an anonymous comment, needs moderation.
    Given <content type> content:
      | title   | body                                                | collection        | status   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <status> |
    Given I am an anonymous user
    When I go to the content page of the type "<content type>" with the title "<title>"
    Then I should see text matching "Add new comment"
    And I fill in "Your name" with "Mr Scandal"
    And I fill in "Email" with "mrscandal@example.com"
    And I fill in "Comment" with "I've heard this story..."
    Then I press "Post comment"
    Then I should see the following success messages:
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And I should not see "I've heard this story..."

    Examples:
      | content type | title               | status    |
      | news         | Scandalous news     | published |
      | event        | Celebrity gathering | published |
      | discussion   | Is gossip bad?      | published |
      | document     | Wikileaks           | published |

  Scenario Outline: Make an authenticated comment, skips moderation.
    Given <content type> content:
      | title   | body                                                | collection        | status   |
      | <title> | How could this ever happen? Moral panic on its way! | Gossip collection | <status> |
    Given I am logged in as "Miss tell tales"
    When I go to the content page of the type "<content type>" with the title "<title>"
    Then I should see text matching "Add new comment"
    And I fill in "Comment" with "Mr scandal was doing something weird the other day."
    Then I press "Post comment"
    Then I should not see the following success messages:
      | Your comment has been queued for review by site administrators and will be published after approval. |
    And I should see text matching "Mr scandal was doing something weird the other day."

    Examples:
      | content type | title               | status    |
      | news         | Scandalous news     | published |
      | event        | Celebrity gathering | published |
      | discussion   | Is gossip bad?      | published |
      | document     | Wikileaks           | published |
