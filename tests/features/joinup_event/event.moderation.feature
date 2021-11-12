@api @terms @group-d
Feature: Event moderation
  In order to manage events
  As a user of the website
  I need to be able to transit the events from one state to another.

  Background:
    Given users:
      | Username        |
      | Salvador Thomas |
      | Rosa Vaughn     |
      | Patricia Lynch  |
    And the following owner:
      | name     |
      | Alma Lee |
    And the following contact:
      | name  | Evs contact             |
      | email | evs.contact@example.com |
    And the following collection:
      | title               | Wet Lords             |
      | description         | The Forgotten Female. |
      | logo                | logo.png              |
      | banner              | banner.jpg            |
      | content creation    | registered users      |
      | moderation          | no                    |
      | state               | validated             |
      | owner               | Alma Lee              |
      | contact information | Evs contact           |
      | topic               | E-inclusion           |
    And the following collection user membership:
      | collection | user           | roles       |
      | Wet Lords  | Rosa Vaughn    | member      |
      | Wet Lords  | Patricia Lynch | facilitator |

  @javascript
  Scenario: Available transitions change to match content creation and moderation settings.
    # For post-moderated collections with content creation set to allow all
    # users to create content, authenticated users that are not members can
    # create events.
    When I am logged in as "Salvador Thomas"
    And I go to the homepage of the "Wet Lords" collection
    And I click "Add event" in the plus button menu
    # Post moderated collections allow publishing content directly.
    And I should see the button "Publish"

    # Edit the collection and set it as moderated.
    When I am logged in as a moderator
    And I go to the homepage of the "Wet Lords" collection
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    And I check the box "Moderated"
    Then I press "Publish"
    And I should see the heading "Wet Lords"

    # The parent group is now pre-moderated: authenticated non-member users
    # should still be able to create events but not to publish them.
    When I am logged in as "Salvador Thomas"
    And I go to the homepage of the "Wet Lords" collection
    And I click "Add event" in the plus button menu
    Then I should not see the button "Publish"
    And I should see the button "Propose"
    But I should see the button "Save as draft"

    # Edit the collection and set it to allow only members to create new
    # content.
    When I am logged in as a moderator
    And I go to the homepage of the "Wet Lords" collection
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    And I check "Closed collection"
    And I wait for AJAX to finish
    And I select the radio button "Only members can create content."
    And I press "Publish"
    # I should now have the possibility to add events.
    When I open the plus button menu
    Then I should see the link "Add event"

    # Non-members should not be able to create events anymore.
    When I am logged in as "Salvador Thomas"
    And I go to the homepage of the "Wet Lords" collection
    Then the plus button menu should be empty

  Scenario: Transit events from one state to another.
    When I am logged in as "Rosa Vaughn"
    And I go to the homepage of the "Wet Lords" collection
    And I click "Add event"
    When I fill in the following:
      | Title             | Rainbow of Worlds                     |
      | Short title       | Rainbow of Worlds                     |
      | Description       | This is going to be an amazing event. |
      | Physical location | Worlds crossroad                      |
    And I fill the start date of the Date widget with "2018-08-30"
    And I fill the start time of the Date widget with "23:59:00"
    And I fill the end date of the Date widget with "2018-09-01"
    And I fill the end time of the Date widget with "00:30:00"
    And I select "EU and European Policies" from "Topic"
    And I press "Save as draft"
    Then I should see the success message 'Event Rainbow of Worlds has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Collection it was created in.'
    And I should see the text "30/08 to 01/09/2018"

    # Publish the content.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Draft"
    And the following fields should be present "Motivation"
    When I fill in "Title" with "The Fire of the Nothing"
    And I press "Publish"
    Then I should see the heading "The Fire of the Nothing"

    # Request modification as facilitator.
    When I am logged in as "Patricia Lynch"
    And I go to the homepage of the "Wet Lords" collection
    And I click "The Fire of the Nothing"
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then I should see the button "Request changes"
    And the current workflow state should be "Published"

    # Implement changes as owner of the document.
    Given I fill in "Motivation" with "Request some regression changes"
    And I press "Request changes"
    When I am logged in as "Rosa Vaughn"
    And I go to the homepage of the "Wet Lords" collection
    And I click "The Fire of the Nothing"
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Proposed"
    When I fill in "Title" with "The event is amazing"
    And I press "Update"
    Then I should see the heading "The Fire of the Nothing"

    # Approve changes as facilitator.
    When I am logged in as "Patricia Lynch"
    And I go to the homepage of the "Wet Lords" collection
    And I click "The Fire of the Nothing"
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then I should see the button "Publish"
    And the current workflow state should be "Proposed"
    And I press "Publish"
    Then I should see the heading "The event is amazing"

  Scenario: Check message draft url when click in Title.
    When I am logged in as "Rosa Vaughn"
    And I go to the homepage of the "Wet Lords" collection
    And I click "Add event"
    When I fill in the following:
      | Title             | Rainbow Beach                     |
      | Short title       | Rainbow Beach                     |
      | Description       | This is going to be an amazing event. |
      | Physical location | Worlds crossroad                      |
    And I fill the start date of the Date widget with "2018-08-30"
    And I fill the start time of the Date widget with "23:59:00"
    And I fill the end date of the Date widget with "2018-09-01"
    And I fill the end time of the Date widget with "00:30:00"
    And I select "EU and European Policies" from "Topic"
    And I press "Save as draft"
    Then I should see the success message 'Event Rainbow Beach has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Collection it was created in.'
    And I click "Rainbow Beach"
    Then I should see the text "This is going to be an amazing event."

  Scenario: Check event when click in My account page.
    When I am logged in as "Rosa Vaughn"
    And I go to the homepage of the "Wet Lords" collection
    And I click "Add event"
    When I fill in the following:
      | Title             | Rainbow vinyls                     |
      | Short title       | Rainbow vinyls                     |
      | Description       | This is going to be an amazing event. |
      | Physical location | Worlds crossroad                      |
    And I fill the start date of the Date widget with "2018-08-30"
    And I fill the start time of the Date widget with "23:59:00"
    And I fill the end date of the Date widget with "2018-09-01"
    And I fill the end time of the Date widget with "00:30:00"
    And I select "EU and European Policies" from "Topic"
    And I press "Save as draft"
    Then I should see the success message 'Event Rainbow vinyls has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Collection it was created in.'
    And I click "My account page"
    Then I should see the heading "Rainbow vinyls"
