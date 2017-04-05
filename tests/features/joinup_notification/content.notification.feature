@api @email
Feature: Content notification system
  As a user of the website
  In order to be up to date with the changes on my content
  I need to be able to receive notification when changes occur.

  Scenario: Facilitators are notified of changes to content made by moderators.
    Given users:
      | Username       | E-mail                     | Roles     |
      | Devyn Queshire | devyn.queshire@example.com |           |
      | Sylvester Toft | sylvester.toft@example.com |           |
      | Reed Mondy     | reed.mondy@example.com     |           |
      | Jerrard Verity | jerrard.verity@example.com | moderator |
    And the following collection:
      | title | Communication tools |
      | state | validated           |
    And the following solution:
      | title | Smoke signals code standard |
      | state | validated                   |
    And the following collection user membership:
      | collection          | user           | roles |
      | Communication tools | Devyn Queshire | owner |
    And the following solution user membership:
      | solution                    | user           | roles       |
      | Smoke signals code standard | Sylvester Toft | facilitator |
      | Smoke signals code standard | Reed Mondy     | facilitator |
    And news content:
      | title                              | headline                            | body                                 | state     | collection          |
      | Infrared long-range communications | Prototype built by a young student. | Bringing Internet access through IR. | validated | Communication tools |
    And event content:
      | title                              | short title         | body                                  | solution                    | start date          |
      | Smoke signals pre-conference party | Smoke signals party | A party thrown before the conference. | Smoke signals code standard | 2017-03-31T16:43:13 |
    And all e-mails have been sent

    When I am logged in as "Jerrard Verity"
    And I go to the "Infrared long-range communications" news page
    And I click "Edit"
    And I enter "Prototype built by a young Italian student." in the Content wysiwyg editor
    And I press "Save new draft"
    Then 1 e-mail should have been sent
    And the following email should have been sent:
      | template  | Message to collection facilitators when a community content is updated by a moderator                          |
      | recipient | Devyn Queshire                                                                                                 |
      | subject   | Joinup: user Jerrard Verity updated a News of your collection                                                  |
      | body      | Devyn Queshire,  Jerrard Verity updated the News "Communication tools" in your Communication tools collection. |

    When I go to the "Smoke signals pre-conference party" event
    And I click "Edit"
    And I fill in "Location" with "Somewhere with a clean sky"
    And I press "Publish"
    Then 2 e-mails should have been sent
    And the following email should have been sent:
      | template  | Message to solution facilitators when a community content is updated by a moderator                                            |
      | recipient | Reed Mondy                                                                                                                     |
      | subject   | Joinup: user Jerrard Verity updated a Event of your solution                                                                   |
      | body      | Dear Reed Mondy,  Jerrard Verity updated the Event "Smoke signals code standard" in your Smoke signals code standard solution. |
