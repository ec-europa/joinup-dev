@api @terms
Feature: Event moderation
  In order to manage events
  As a user of the website
  I need to be able to transit the events from one state to another.

  Background:
    Given users:
      | name            |
      | Salvador Thomas |
      | Rosa Vaughn     |
      | Patricia Lynch  |
    And the following owner:
      | name     |
      | Alma Lee |
    And the following collection:
      | title             | Wet Lords             |
      | description       | The Forgotten Female. |
      | logo              | logo.png              |
      | banner            | banner.jpg            |
      | elibrary creation | registered users      |
      | moderation        | no                    |
      | state             | validated             |
      | owner             | Alma Lee              |
      | policy domain     | E-inclusion           |
    And the following collection user membership:
      | collection | user           | roles       |
      | Wet Lords  | Rosa Vaughn    | member      |
      | Wet Lords  | Patricia Lynch | facilitator |

  Scenario: Available transitions change per eLibrary and moderation settings.
    # For post moderated collections with eLibrary set to allow all users to
    # create content, authenticated users that are not members can create
    # events.
    When I am logged in as "Salvador Thomas"
    And go to the homepage of the "Wet Lords" collection
    And I click "Add event" in the plus button menu
    # Post moderated collections allow publishing content directly.
    And I should see the button "Publish"

    # Edit the collection and set it as moderated.
    When I am logged in as a moderator
    And I go to the homepage of the "Wet Lords" collection
    And I click "Edit" in the "Entity actions" region
    And I check the box "Moderated"
    Then I press "Publish"
    And I should see the heading "Wet Lords"

    # The parent group is now pre-moderated: authenticated non-member users
    # should still be able to create events but not to publish them.
    When I am logged in as "Salvador Thomas"
    And I go to the homepage of the "Wet Lords" collection
    And I click "Add event" in the plus button menu
    Then I should not see the button "Publish"
    And I should not see the button "Propose"
    But I should see the button "Save as draft"

    # Edit the collection and set it to allow only members to create new
    # content.
    When I am logged in as a moderator
    And I go to the homepage of the "Wet Lords" collection
    And I click "Edit" in the "Entity actions" region
    And I check "Closed collection"
    And I select "Only members can create new content." from "eLibrary creation"
    Then I press "Publish"
    And I should see the link "Add event"

    # Non-members should not be able to create events anymore.
    When I am logged in as "Salvador Thomas"
    And I go to the homepage of the "Wet Lords" collection
    Then I should not see the link "Add event"

  Scenario: Transit events from one state to another.
    When I am logged in as "Rosa Vaughn"
    And I go to the homepage of the "Wet Lords" collection
    And I click "Add event"
    When I fill in the following:
      | Title       | Rainbow of Worlds                     |
      | Short title | Rainbow of Worlds                     |
      | Description | This is going to be an amazing event. |
    And I fill in "Start date" with the date "2018-08-30"
    And I fill in "Start date" with the time "23:59:00"
    And I press "Save as draft"
    Then I should see the success message "Event Rainbow of Worlds has been created"

    # Publish the content.
    When I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "The Fire of the Nothing"
    And I press "Publish"
    Then I should see the heading "The Fire of the Nothing"

    # Request modification as facilitator.
    When I am logged in as "Patricia Lynch"
    And I go to the homepage of the "Wet Lords" collection
    And I click "The Fire of the Nothing"
    And I click "Edit" in the "Entity actions" region
    Then I should see the button "Request changes"
    And I press "Request changes"

    # Implement changes as owner of the event.
    When I am logged in as "Rosa Vaughn"
    And I go to the homepage of the "Wet Lords" collection
    And I click "The Fire of the Nothing"
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "The event is amazing"
    And I press "Update proposed"
    Then I should see the heading "The Fire of the Nothing"

    # Approve changes as facilitator.
    When I am logged in as "Patricia Lynch"
    And I go to the homepage of the "Wet Lords" collection
    And I click "The Fire of the Nothing"
    And I click "Edit" in the "Entity actions" region
    Then I should see the button "Approve proposed"
    And I press "Approve proposed"
    Then I should see the heading "The event is amazing"
