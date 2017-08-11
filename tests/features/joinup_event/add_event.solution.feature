@api
Feature: "Add event" visibility options.
  In order to manage events
  As a solution member
  I need to be able to add "Event" content through UI.

  Scenario: "Add event" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following solutions:
      | title           | logo     | banner     | state     |
      | Ragged Tower    | logo.png | banner.jpg | validated |
      | Prince of Magic | logo.png | banner.jpg | validated |
    And the following collection:
      | title      | Collective Ragged tower       |
      | logo       | logo.png                      |
      | banner     | banner.jpg                    |
      | affiliates | Ragged Tower, Prince of Magic |
      | state      | validated                     |

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

  Scenario: Add event as a facilitator.
    Given solutions:
      | title                | logo     | banner     | state     |
      | The Luscious Bridges | logo.png | banner.jpg | validated |
    And the following collection:
      | title      | Collective The Luscious Bridges |
      | logo       | logo.png                        |
      | banner     | banner.jpg                      |
      | affiliates | The Luscious Bridges            |
      | state      | validated                       |
    And I am logged in as a facilitator of the "The Luscious Bridges" solution
    When I go to the homepage of the "The Luscious Bridges" solution
    And I click "Add event" in the plus button menu
    Then I should see the heading "Add event"
    And the following fields should be present "Title, Short title, Description, Agenda, Logo, Additional address info, Contact email, Website, Policy domain, Add a new file, Scope, Spatial coverage"

    # The sections about managing revisions and groups should not be visible.
    And I should not see the text "Revision information"
    And the following fields should not be present "Groups audience, Other groups, Create new revision, Revision log message, Shared in, Motivation"

    # Check required fields.
    And I attach the file "test.zip" to "Add a new file"
    And I press "Upload"
    And I press "Publish"
    Then I should see the following lines of text:
      | Title field is required.            |
      | Description field is required.      |
      | File description field is required. |
      | Location field is required.         |

    When I fill in the following:
      | Title            | An amazing event                      |
      | Short title      | Amazing event                         |
      | Description      | This is going to be an amazing event. |
      | Location         | Rue Belliard, 28                      |
      | File description | Your free ticket                      |
    And I fill the start date of the "Date" widget with "2018-08-29"
    And I fill the start time of the Date widget with "23:59:00"

    # Test that a helpful message is shown when a field is only partially filled in.
    And I fill the end date of the Date widget with "2018-08-29"
    And I clear the end time of the "Date" widget
    And I press "Save as draft"
    Then I should see the error message "The date and time should both be entered in the End date field"

    When I fill the end time of the Date widget with "23:59:00"
    And I press "Save as draft"
    Then I should see the heading "An amazing event"
    And I should see the success message "Event An amazing event has been created."
    And I should see the text "29 August 2018"
    And the "The Luscious Bridges" solution has a event titled "An amazing event"
    # Check that the link to the event is visible on the solution page.
    When I go to the homepage of the "The Luscious Bridges" solution
    Then I should see the link "An amazing event"
