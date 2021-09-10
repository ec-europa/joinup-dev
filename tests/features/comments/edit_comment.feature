@api @group-a
Feature: Edit a comment
  As a moderator of the site,
  I should receive notifications when someone edits a comment.

  Scenario: Edit a comment.
    Given the following collections:
      | title              | state     | closed |
      | Structural pension | validated | no     |
    And users:
      | Username               | E-mail                             | Roles     | First name | Family name |
      | Sons of anarchy        | sonsofanarchy@example.com          |           | Sons       | Anarchy     |
      | Comment edit moderator | comment.edit.moderator@example.com | moderator | Steeve     | Roberts     |
    And news content:
      | title            | body                                                | collection         | state     |
      | Paying with cash | How could this ever happen? Moral panic on its way! | Structural pension | validated |
    And comments:
      | subject                       | field_body       | author          | parent           |
      | This comment should be edited | Let's all use it | Sons of anarchy | Paying with cash |

    Given I am logged in as "Sons of anarchy"
    When I go to the "Paying with cash" news
    # Sons' comment is the only comment available.
    And I click "Edit" in comment #1
    And I fill in "Create comment" with "Cracking the web."
    And I wait for the spam protection time limit to pass
    And I press "Post comment"
    Then the email sent to "Comment edit moderator" with subject "Joinup: A comment has been updated." contains the following lines of text:
      | text                                                                               |
      | Sons Anarchy updated the comment in "Paying with cash".                            |
      | If you think this action is not clear or not due, please contact Joinup Support at |
