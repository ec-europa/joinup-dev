@api
Feature: Organic Groups integration
  In order to participate in the activities of a collection
  As an authenticated user
  I need to be able to join and leave collections

  Scenario: Join a collection
    Given the following collection:
      | name            | Überwaldean land eels |
      | author          | Arnold Sideways       |
    And users:
      | name           | role          |
      | Madame Sharn   | authenticated |
      | Goodie Whemper | authenticated |

    # Initially the collection should only have 1 member, the group manager.
    Then the "Überwaldean land eels" collection should have 1 member

    # Anonymous users should not be able to join a collection.
    Given I am an anonymous user
    When I go to the homepage of the "Überwaldean land eels" collection
    Then I should not see the "Join this collection" button

    # Authenticated users can join. The Join button should be hidden if the user
    # already is a member of the collection.
    Given I am logged in as "Madame Sharn"
    When I go to the homepage of the "Überwaldean land eels" collection
    Then I should see the "Join this collection" button
    When I press the "Join this collection" button
    Then I should see the success message "You are now a member of Überwaldean land eels."
    And the "Überwaldean land eels" collection should have 2 members
    When I go to the homepage of the "Überwaldean land eels" collection
    Then I should not see the "Join this collection" button

    # Check that a second authenticated user can join, the form should not be
    # cached.
    Given I am logged in as "Goodie Whemper"
    When I go to the homepage of the "Überwaldean land eels" collection
    And I press the "Join this collection" button
    Then I should see the success message "You are now a member of Überwaldean land eels."
    And the "Überwaldean land eels" collection should have 3 members
