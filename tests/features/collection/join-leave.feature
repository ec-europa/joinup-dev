@api
Feature: Joining and leaving collections through the web interface
  In order to participate in the activities of a collection
  As an authenticated user
  I need to be able to join and leave collections

  Scenario: Joining and leaving a collection
    Given collections:
      | title                       | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | elibrary creation | moderation | state     |
      | Überwaldean Land Eels       | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | no     | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | facilitators      | yes        | validated |
      | Folk Dance and Song Society | Cats are cool!                             | http://mashable.com/category/cats/     | yes    | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | members           | no         | validated |
    And users:
      | Username           |
      | Madame Sharn       |
      | Goodie Whemper     |
      | Kathie Cumberwatch |
    And the following collection user memberships:
      | collection                  | user               |
      | Folk Dance and Song Society | Kathie Cumberwatch |

    # Note that when a group is created through the UI, the logged in user will
    # automatically become the group manager, so the group will always have at
    # least 1 member. In this case however we are creating the group through the
    # API and there is no logged in user, so the collection should not have any
    # members.
    Then the "Überwaldean Land Eels" collection should have 0 active members
    And the "Folk Dance and Song Society" collection should have 1 active members

    # Anonymous users should not be able to join or leave a collection.
    Given I am an anonymous user
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should not see the "Join this collection" button
    And I should not see the link "Leave this collection"

    # Authenticated users can join. The Join button should be hidden if the user
    # already is a member of the collection.
    Given I am logged in as "Madame Sharn"
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should see the "Join this collection" button
    When I press the "Join this collection" button
    Then I should see the success message "You are now a member of Überwaldean Land Eels."
    And the "Überwaldean Land Eels" collection should have 1 active member
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should not see the "Join this collection" button
    And I should not see the link "Edit"
    But I should see the link "Leave this collection"

    # Check that it is possible to join a second collection.
    When I go to the homepage of the "Folk Dance and Song Society" collection
    Then I should see the "Join this collection" button
    When I press the "Join this collection" button
    Then I should see the success message "Your membership to the Folk Dance and Song Society collection is under approval."
    And the "Folk Dance and Song Society" collection should have 1 active member
    And the "Folk Dance and Song Society" collection should have 1 pending member
    When I go to the homepage of the "Folk Dance and Song Society" collection
    Then I should not see the "Join this collection" button
    And I should not see the "Leave this collection" button
    # Pending membership.
    But I should see the link "Membership is pending"

    # Check that a second authenticated user can join, the form should not be
    # cached.
    Given I am logged in as "Goodie Whemper"
    When I go to the homepage of the "Überwaldean Land Eels" collection
    And I press the "Join this collection" button
    Then I should see the success message "You are now a member of Überwaldean Land Eels."
    And the "Überwaldean Land Eels" collection should have 2 active members

    # Check that both users can leave their respective collections if their membership is approved.
    When I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Überwaldean Land Eels collection?"
    And I should not see the link "Leave this collection"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Überwaldean Land Eels."
    And I should see the "Join this collection" button
    And the "Überwaldean Land Eels" collection should have 1 active member

    When I am logged in as "Madame Sharn"
    And I go to the homepage of the "Überwaldean Land Eels" collection
    And I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Überwaldean Land Eels collection?"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Überwaldean Land Eels."
    And I should see the "Join this collection" button
    And the "Überwaldean Land Eels" collection should have 0 active members

    When I am logged in as "Kathie Cumberwatch"
    And I go to the homepage of the "Folk Dance and Song Society" collection
    And I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Folk Dance and Song Society collection?"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Folk Dance and Song Society."
    And I should see the "Join this collection" button
    And the "Folk Dance and Song Society" collection should have 0 active members
    And the "Folk Dance and Song Society" collection should have 1 pending member
