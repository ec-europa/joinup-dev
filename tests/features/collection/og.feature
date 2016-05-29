@api
Feature: Organic Groups integration
  In order to participate in the activities of a collection
  As an authenticated user
  I need to be able to join and leave collections

  Scenario: Joining and leaving a collection
    Given collections:
      | title                       | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | elibrary creation | moderation |
      | Überwaldean Land Eels       | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes    | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | facilitators      | yes        |
      | Folk Dance and Song Society | Cats are cool!                             | http://mashable.com/category/cats/     | no     | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | members           | no         |
    And users:
      | name           |
      | Madame Sharn   |
      | Goodie Whemper |

    # Initially the collection should only have 1 member, the group manager.
    Then the "Überwaldean Land Eels" collection should have 1 member
    And the "Folk Dance and Song Society" collection should have 1 member

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
    And the "Überwaldean Land Eels" collection should have 2 members
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should not see the "Join this collection" button
    And I should not see the link "Edit"
    But I should see the link "Leave this collection"

    # Check that it is possible to join a second collection.
    When I go to the homepage of the "Folk Dance and Song Society" collection
    Then I should see the "Join this collection" button
    When I press the "Join this collection" button
    Then I should see the success message "You are now a member of Folk Dance and Song Society."
    And the "Folk Dance and Song Society" collection should have 2 members
    When I go to the homepage of the "Folk Dance and Song Society" collection
    Then I should not see the "Join this collection" button
    But I should see the link "Leave this collection"

    # Check that a second authenticated user can join, the form should not be
    # cached.
    Given I am logged in as "Goodie Whemper"
    When I go to the homepage of the "Überwaldean Land Eels" collection
    And I press the "Join this collection" button
    Then I should see the success message "You are now a member of Überwaldean Land Eels."
    And the "Überwaldean Land Eels" collection should have 3 members

    # Check that both users can leave their respective collections.
    When I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Überwaldean Land Eels collection?"
    And I should not see the link "Leave this collection"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Überwaldean Land Eels."
    And I should see the "Join this collection" button
    And the "Überwaldean Land Eels" collection should have 2 members

    When I am logged in as "Madame Sharn"
    And I go to the homepage of the "Überwaldean Land Eels" collection
    And I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Überwaldean Land Eels collection?"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Überwaldean Land Eels."
    And I should see the "Join this collection" button
    And the "Überwaldean Land Eels" collection should have 1 member

    When I go to the homepage of the "Folk Dance and Song Society" collection
    And I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Folk Dance and Song Society collection?"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Folk Dance and Song Society."
    And I should see the "Join this collection" button
    And the "Folk Dance and Song Society" collection should have 1 member

  Scenario: Edit a Collection
    Given collections:
      | title                       | logo     | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | elibrary creation | moderation |
      | Überwaldean Land Eels       | logo.png | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes    | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | facilitators      | yes        |
      | Folk Dance and Song Society | logo.png | Cats are cool!                             | http://mashable.com/category/cats/     | no     | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | members           | no         |
    And users:
      | name             | roles         |
      | Collection admin | administrator |
      | Madame Sharn     |               |
      | Goodie Whemper   |               |
    # Administrators should be able to edit the collection. This is temporary
    # and is provided for the convenience of the user acceptance testers so they
    # can log in as administrator and edit existing collections for testing
    # purposes.
    # @todo This should be only possible as facilitator or collection owner.
    # @see ISAICP-2362
    Given I am logged in as "Collection admin"
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should see the link "Edit"

    # Edit a collection.
    When I go to the "Überwaldean Land Eels" collection edit form
    Then the following fields should be present "Title, Description, Abstract, Contact information, Owner, Policy domain, Topic, Spacial coverage, Affiliates, Closed collection, eLibrary creation, Moderated"
    And I fill in "Title" with "Überwaldean Sea Eels"
    And I press the "Save" button
    Then I should see the heading "Überwaldean Sea Eels"
