@api @email
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
    And all e-mails have been sent
    When I go to the "Paying with cash" news
    # Sons' comment is the only comment available.
    And I click the contextual link "Edit comment" in the "Comment" region
    And I fill in "Create comment" with "Cracking the web."
    And I select "Simple HTML" from "Text format"
    Then I press "Post comment"
    And the following email should have been sent:
      | recipient | Comment edit moderator                                  |
      | subject   | Joinup: A comment has been updated.                     |
      | body      | Sons Anarchy updated the comment in "Paying with cash". |
