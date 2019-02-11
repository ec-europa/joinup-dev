@api
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

  Scenario: Add event as a facilitator.
    Given collections:
      | title            | logo     | banner     | state     |
      | Stream of Dreams | logo.png | banner.jpg | validated |
    And I am logged in as a facilitator of the "Stream of Dreams" collection

    When I go to the homepage of the "Stream of Dreams" collection
    And I click "Add event" in the plus button menu
    Then I should see the heading "Add event"
    And the following fields should be present "Title, Short title, Description, Agenda, Logo, Contact email, Website, Location, Organisation, Organisation type, Policy domain, Add a new file, Keywords, Scope, Spatial coverage"
    # The entity is new, so the current workflow state should not be shown.
    And the following fields should not be present "Current workflow state, Motivation"
    And the following fields should not be present "Shared in"

    # Check required fields.
    And I attach the file "test.zip" to "Add a new file"
    And I press "Upload"
    And I press "Publish"
    Then I should see the following lines of text:
      | Title field is required.            |
      | Description field is required.      |
      | File description field is required. |

    When I fill in the following:
      | Title            | An amazing event                      |
      | Short title      | Amazing event                         |
      | Description      | This is going to be an amazing event. |
      | Location         | Rue Belliard 28, Brussels, Belgium    |
      | File description | Taxi discount voucher.                |
      | Spatial coverage | France                                |
    And I press "Add another item" at the "Online location" field
    And I enter the following for the "Online location" link field:
      | URL                          | Title           |
      | https://joinup.ec.europa.eu/ | Joinup homepage |
      | https://drupal.org/          |                 |
    And I fill the start date of the Date widget with "2018-08-29"
    And I fill the start time of the Date widget with "23:59:59"
    And I fill the end date of the Date widget with "2018-08-30"
    And I fill the end time of the Date widget with "12:57:00"
    # And I fill in "Scope" with values "pan_european, national"
    And I select "National" from "Scope"
    And  I additionally select "Regional" from "Scope"
    And I press "Save as draft"
    Then I should see the heading "An amazing event"
    But I should not see the text "National"
    And I should not see the text "Regional"
    But I should see the success message "Event An amazing event has been created."
    And I should see the text "29 to 30 August 2018"
    And I should see a map centered on latitude 4.370375 and longitude 50.842156
    And I should see the link "Joinup homepage"
    And I should see the link "https://drupal.org"
    And the "Stream of Dreams" collection has a event titled "An amazing event"
    And I should not see the text "France"
    # Check that the link to the event is visible on the collection page.
    When I go to the homepage of the "Stream of Dreams" collection
    Then I should see the link "An amazing event"
