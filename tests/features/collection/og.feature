@api
Feature: Organic Groups integration
  In order to participate in the activities of a collection
  As an authenticated user
  I need to be able to join and leave collections


  Scenario: Joining and leaving a collection
    Given collections:
      | title                       | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | elibrary creation | moderation | state     |
      | Überwaldean Land Eels       | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes    | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | facilitators      | yes        | validated |
      | Folk Dance and Song Society | Cats are cool!                             | http://mashable.com/category/cats/     | no     | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | members           | no         | validated |
    And users:
      | Username       |
      | Madame Sharn   |
      | Goodie Whemper |

    # Initially the collection should only have 1 member, the group manager
    # but since this is created by the api, no user is logged so no user
    # is assigned as group owner and the collection should not have members.
    Then the "Überwaldean Land Eels" collection should have 0 members
    And the "Folk Dance and Song Society" collection should have 0 members

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
    And the "Überwaldean Land Eels" collection should have 1 member
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should not see the "Join this collection" button
    And I should not see the link "Edit"
    But I should see the link "Leave this collection"

    # Check that it is possible to join a second collection.
    When I go to the homepage of the "Folk Dance and Song Society" collection
    Then I should see the "Join this collection" button
    When I press the "Join this collection" button
    Then I should see the success message "You are now a member of Folk Dance and Song Society."
    And the "Folk Dance and Song Society" collection should have 1 member
    When I go to the homepage of the "Folk Dance and Song Society" collection
    Then I should not see the "Join this collection" button
    But I should see the link "Leave this collection"

    # Check that a second authenticated user can join, the form should not be
    # cached.
    Given I am logged in as "Goodie Whemper"
    When I go to the homepage of the "Überwaldean Land Eels" collection
    And I press the "Join this collection" button
    Then I should see the success message "You are now a member of Überwaldean Land Eels."
    And the "Überwaldean Land Eels" collection should have 2 members

    # Check that both users can leave their respective collections.
    When I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Überwaldean Land Eels collection?"
    And I should not see the link "Leave this collection"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Überwaldean Land Eels."
    And I should see the "Join this collection" button
    And the "Überwaldean Land Eels" collection should have 1 member

    When I am logged in as "Madame Sharn"
    And I go to the homepage of the "Überwaldean Land Eels" collection
    And I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Überwaldean Land Eels collection?"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Überwaldean Land Eels."
    And I should see the "Join this collection" button
    And the "Überwaldean Land Eels" collection should have 0 members

    When I go to the homepage of the "Folk Dance and Song Society" collection
    And I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Folk Dance and Song Society collection?"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Folk Dance and Song Society."
    And I should see the "Join this collection" button
    And the "Folk Dance and Song Society" collection should have 0 members

  @terms
  Scenario: Edit a Collection
    Given the following owner:
      | name                 | type                    |
      | Organisation example | Non-Profit Organisation |
    Given collections:
      | title                 | logo     | banner     | abstract                                   | access url                             | closed | creation date    | description                               | elibrary creation | moderation | policy domain     | owner                | state |
      | Überwaldean Land Eels | logo.png | banner.jpg | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes    | 28-01-1995 12:05 | The Afghan Hound is elegance personified. | facilitators      | yes        | Supplier exchange | Organisation example | draft |
    And users:
      | Username       |
      | Madame Sharn   |
      | Goodie Whemper |
    Given I am logged in as a facilitator of the "Überwaldean Land Eels" collection
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should see the link "Edit"

    # Edit a collection.
    When I go to the "Überwaldean Land Eels" collection edit form
    Then the following fields should be present "Title, Description, Abstract, Policy domain, Spatial coverage, Affiliates, Closed collection, eLibrary creation, Moderated"
    And the following field widgets should be present "Contact information, Owner"
    And I fill in "Title" with "Überwaldean Sea Eels"
    And I press the "Save as draft" button
    Then I should see the heading "Überwaldean Sea Eels"
