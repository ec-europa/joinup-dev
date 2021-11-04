@api @group-d
Feature: "Add event" visibility options.
  In order to manage events
  As a collection member
  I need to be able to add "Event" content through UI.

  Scenario: "Add event" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following collections:
      | title               | logo     | banner     | state     |
      | The Stripped Stream | logo.png | banner.jpg | validated |
      | Years in the Nobody | logo.png | banner.jpg | validated |

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "The Stripped Stream" collection
    Then I should not see the link "Add event"

    When I am an anonymous user
    And I go to the homepage of the "The Stripped Stream" collection
    Then I should not see the link "Add event"

    When I am logged in as a member of the "The Stripped Stream" collection
    And I go to the homepage of the "The Stripped Stream" collection
    Then I should see the link "Add event"

    When I am logged in as a "facilitator" of the "The Stripped Stream" collection
    And I go to the homepage of the "The Stripped Stream" collection
    Then I should see the link "Add event"
    # I should not be able to add a event to a different collection
    When I go to the homepage of the "Years in the Nobody" collection
    Then I should not see the link "Add event"

    When I am logged in as a "moderator"
    And I go to the homepage of the "The Stripped Stream" collection
    Then I should see the link "Add event"

  @terms @uploadFiles:test.zip
  Scenario: Add event as a facilitator.
    Given collections:
      | title            | logo     | banner     | state     |
      | Stream of Dreams | logo.png | banner.jpg | validated |
    And I am logged in as a facilitator of the "Stream of Dreams" collection

    When I go to the homepage of the "Stream of Dreams" collection
    And I click "Add event" in the plus button menu
    Then I should see the heading "Add event"
    And the following fields should be present "Title, Short title, Description, Agenda, Logo, Contact email, Website, Physical location, Organisation, Organisation type, Topic, Add a new file, Keywords, Scope, Geographical coverage"
    # The entity is new, so the current workflow state should not be shown.
    And the following fields should not be present "Current workflow state, Motivation"
    And the following fields should not be present "Shared on"

    # Check required fields.
    And I attach the file "test.zip" to "Add a new file"
    And I press "Upload"
    And I press "Publish"
    Then I should see the following lines of text:
      | Title field is required.                       |
      | Description field is required.                 |
      | The Attachments field description is required. |

    When I fill in the following:
      | Title                 | An amazing event                      |
      | Short title           | Amazing event                         |
      | Description           | This is going to be an amazing event. |
      | File description      | Taxi discount voucher.                |
      | Geographical coverage | France                                |
    And I press "Add another item" at the "Virtual location" field
    And I fill the start date of the Date widget with "2018-08-29"
    And I fill the start time of the Date widget with "23:59:59"
    And I fill the end date of the Date widget with "2018-08-30"
    And I fill the end time of the Date widget with "12:57:00"
    # And I fill in "Scope" with values "pan_european, national"
    And I select "National" from "Scope"
    And  I additionally select "Regional" from "Scope"
    And I press "Save as draft"
    Then I should see the following error messages:
      | error messages                                   |
      | At least one location field should be filled in. |
      | Topic field is required.                         |

    When I fill in "Physical location" with "Rue Belliard 28, Brussels, Belgium"
    And I enter the following for the "Virtual location" link field:
      | URL                          | Title           |
      | https://joinup.ec.europa.eu/ | Joinup homepage |
      | https://drupal.org/          |                 |
    And I select "EU and European Policies" from "Topic"
    And I press "Save as draft"
    Then I should see the heading "An amazing event"
    But I should not see the text "National"
    And I should not see the text "Regional"
    And I should see the text "Rue Belliard 28, Brussels, Belgium"
    But I should see the success message 'Event An amazing event has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Collection it was created in.'
    And I should see the text "Event date:"
    And I should see the text "29 to 30/08/2018"
    And I should see a map centered on latitude 4.370375 and longitude 50.842156
    And I should see the following marker on the map:
      | name        | An amazing event                   |
      | description | Rue Belliard 28, Brussels, Belgium |
      | latitude    | 50.842156                          |
      | longitude   | 4.370375                           |
    And I should see the link "Joinup homepage"
    And I should see the link "https://drupal.org"
    And the "Stream of Dreams" collection has a event titled "An amazing event"
    And I should not see the text "France"
    # Check that the link to the event is visible on the collection page.
    When I go to the homepage of the "Stream of Dreams" collection
    Then I should see the link "An amazing event"

    # Check if the event date range is shown in an understandable format if the
    # event spans a month or year boundary.
    When I go to the edit form of the "An amazing event" event
    And I fill the end date of the Date widget with "2018-09-01"
    And I press "Save as draft"
    Then I should see the text "29/08 to 01/09/2018"

    When I go to the edit form of the "An amazing event" event
    And I fill the end date of the Date widget with "2019-01-02"
    And I press "Save as draft"
    Then I should see the text "29/08/2018 to 02/01/2019"

  @javascript @generateMedia @uploadFiles:logo.png
  Scenario: Test the image library widget.
    Given the following collection:
      | title | Stream of Dreams |
      | state | validated        |
    And event content:
      | title             | collection       | body      | online location              | state     |
      | The Great Opening | Stream of Dreams | It opens! | webinar - http://example.com | validated |

    Given I am logged in as a moderator

    # Upload works.
    When I go to the edit form of the "The Great Opening" event
    And I attach the file "logo.png" to "Logo"
    And I wait for AJAX to finish

    # Picking-up pre-uploaded images works.
    When I remove the file from "Logo"
    And I wait for AJAX to finish
    And I select image #7 as event logo
    And I wait for AJAX to finish
    And I press "Update"
    And the "The Great Opening" event logo is image #7
