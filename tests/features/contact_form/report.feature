@api @group-b
Feature: Submit the contact form
  In order to avoid having inappropriate content on the website
  As a moderator, group administrator or content owner
  I need to receive email when the content is reported

  Scenario: Receive email when content is reported
    Given users:
      | Username         | Roles     | E-mail                    | First name | Family name |
      | Report moderator | moderator | report_moderator@test.com | Frank      | Sinatra     |
      | Report user      |           | report_user@test.com      | Rudi       | Sinatra     |
      | Report owner     |           | report_owner@test.com     | Clark      | The machine |
    And collections:
      | title                                 | state     | abstract     | description   |
      | Collection with inappropriate content | validated | No one cares | No one cares. |
    And the following collection user memberships:
      | collection                            | user         | roles              |
      | Collection with inappropriate content | Report owner | owner, facilitator |
    And event content:
      | title           | author      | body | location  | collection                            | field_state |
      | Event to report | Report user | body | Somewhere | Collection with inappropriate content | validated   |

    # There should be a link to the contact form in the footer.
    Given I am not logged in
    When I go to the "Event to report" event
    And I click "Report"
    And I fill in the following:
      | First name     | Balourdos                                                                      |
      | Last name      | Tsolias                                                                        |
      | Organisation   |                                                                                |
      | E-mail address | balourdos@example.rg                                                           |
      | Subject        | This content has invalid location                                              |
      | Message        | The location described as "Somewhere" could not be found by my map application |
    # We need to wait 5 seconds for the spam protection time limit to pass.
    Then I wait for the spam protection time limit to pass
    And I press "Submit"

    # The moderator, the collection owner and the owner should receive the notifications.
    Then the following email should have been sent:
      | template  | Report contact form                                                                                                                                                                             |
      | recipient | Report moderator                                                                                                                                                                                |
      | subject   | Joinup: This content has invalid location                                                                                                                                                       |
      | body      | Balourdos Tsolias has reported the item "Event to report" as abusive due to The location described as "Somewhere" could not be found by my map application. You can access the reported content |
    And the following email should have been sent:
      | template  | Report contact form                                                                                                                                                                             |
      | recipient | Report owner                                                                                                                                                                                    |
      | subject   | Joinup: This content has invalid location                                                                                                                                                       |
      | body      | Balourdos Tsolias has reported the item "Event to report" as abusive due to The location described as "Somewhere" could not be found by my map application. You can access the reported content |
    And the following email should have been sent:
      | recipient_mail | balourdos@example.rg               |
      | subject        | Joinup: Contact form notification  |
      | body           | We will reply as soon as possible. |
    And I should see the heading "Event to report"

    # Now the reporter requires also a copy of its message.
    When I click "Report"
    And I fill in the following:
      | First name     | Gavin                                                                                                     |
      | Last name      | Belson                                                                                                    |
      | Organisation   |                                                                                                           |
      | E-mail address | gbelson@hooli.com                                                                                         |
      | Subject        | Making the World a Better Place                                                                           |
      | Message        | I don't want to live in a world where someone else is making the world a better place better than we are. |
    And I check the box "Send yourself a copy"

    Given I wait for the spam protection time limit to pass
    And I press "Submit"
    Then the following email should have been sent:
      | recipient_mail | gbelson@hooli.com                                                                                         |
      | subject        | Joinup: Contact form notification                                                                         |
      | body           | I don't want to live in a world where someone else is making the world a better place better than we are. |
