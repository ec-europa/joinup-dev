@api @group-d
Feature: "Add event" visibility options.
  In order to manage events
  As a solution member
  I need to be able to add "Event" content through UI.

  Scenario: "Add event" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following collection:
      | title  | Collective Ragged tower |
      | logo   | logo.png                |
      | banner | banner.jpg              |
      | state  | validated               |
    And the following solutions:
      | title           | collection              | logo     | banner     | state     |
      | Ragged Tower    | Collective Ragged tower | logo.png | banner.jpg | validated |
      | Prince of Magic | Collective Ragged tower | logo.png | banner.jpg | validated |

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Ragged Tower" solution
    Then I should not see the link "Add event"

    When I am an anonymous user
    And I go to the homepage of the "Ragged Tower" solution
    Then I should not see the link "Add event"

    When I am logged in as a "facilitator" of the "Ragged Tower" solution
    And I go to the homepage of the "Ragged Tower" solution
    Then I should see the link "Add event"
    # I should not be able to add a event to a different solution
    When I go to the homepage of the "Prince of Magic" solution
    Then I should not see the link "Add event"

    When I am logged in as a "moderator"
    And I go to the homepage of the "Ragged Tower" solution
    Then I should see the link "Add event"

  @terms @uploadFiles:test.zip
  Scenario: Add event as a facilitator.
    Given the following collection:
      | title  | Collective The Luscious Bridges |
      | logo   | logo.png                        |
      | banner | banner.jpg                      |
      | state  | validated                       |
    And the following solutions:
      | title                | collection                      | logo     | banner     | state     |
      | The Luscious Bridges | Collective The Luscious Bridges | logo.png | banner.jpg | validated |
    And I am logged in as a facilitator of the "The Luscious Bridges" solution
    When I go to the homepage of the "The Luscious Bridges" solution
    And I click "Add event" in the plus button menu
    Then I should see the heading "Add event"
    And the following fields should be present "Title, Short title, Description, Agenda, Logo, Contact email, Website, Topic, Add a new file, Scope, Geographical coverage"
    And the following fields should not be present "Shared on, Motivation"

    # Check required fields.
    And I attach the file "test.zip" to "Add a new file"
    And I press "Upload"
    And I press "Publish"
    Then I should see the following lines of text:
      | Title field is required.                         |
      | Description field is required.                   |
      | The Attachments field description is required.   |
      | At least one location field should be filled in. |

    When I fill in the following:
      | Title             | An amazing event                      |
      | Short title       | Amazing event                         |
      | Description       | This is going to be an amazing event. |
      | Physical location | Rue Belliard, 28                      |
      | File description  | Your free ticket                      |
    And I fill the start date of the "Date" widget with "2018-08-29"
    And I fill the start time of the Date widget with "23:59:00"

    # Test that a helpful message is shown when a field is only partially filled in.
    And I fill the end date of the Date widget with "2018-08-29"
    And I clear the end time of the "Date" widget
    And I press "Save as draft"
    Then I should see the following error messages:
      | error messages                                                 |
      | The date and time should both be entered in the End date field |
      | Topic field is required.                                       |

    When I fill the end time of the Date widget with "23:59:00"
    And I select "EU and European Policies" from "Topic"
    And I press "Save as draft"
    Then I should see the heading "An amazing event"
    And I should see the success message 'Event An amazing event has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Solution it was created in.'
    And I should see the text "29/08/2018"
    And the "The Luscious Bridges" solution has a event titled "An amazing event"
    # Check that the link to the event is visible on the solution page.
    When I go to the homepage of the "The Luscious Bridges" solution
    Then I should see the link "An amazing event"
