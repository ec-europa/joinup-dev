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
      | Username       |
      | Madame Sharn   |
      | Goodie Whemper |

    # Note that when a group is created through the UI, the logged in user will
    # automatically become the group manager, so the group will always have at
    # least 1 member. In this case however we are creating the group through the
    # API and there is no logged in user, so the collection should not have any
    # members.
    Then the "Überwaldean Land Eels" collection should have 0 active members
    And the "Folk Dance and Song Society" collection should have 0 active members

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
    And "Madame Sharn" should be subscribed to "document, discussion, news, event" content bundles in the "Überwaldean Land Eels" collection
    When I go to the homepage of the "Überwaldean Land Eels" collection
    Then I should not see the "Join this collection" button
    And I should not see the link "Edit"
    But I should see the link "Leave this collection"

    # Check that it is possible to join a closed collection.
    When I go to the homepage of the "Folk Dance and Song Society" collection
    Then I should see the "Join this collection" button
    When I press the "Join this collection" button
    Then I should see the success message "Your membership to the Folk Dance and Song Society collection is under approval."
    And the "Folk Dance and Song Society" collection should have 0 active members
    And the "Folk Dance and Song Society" collection should have 1 pending member
    When I go to the homepage of the "Folk Dance and Song Society" collection
    Then I should not see the "Join this collection" button
    And I should not see the "Leave this collection" button
    But I should see the link "Membership is pending"

    # Check that a second authenticated user can join, the form should not be
    # cached.
    Given I am logged in as "Goodie Whemper"
    When I go to the homepage of the "Überwaldean Land Eels" collection
    And I press the "Join this collection" button
    Then I should see the success message "You are now a member of Überwaldean Land Eels."
    And the "Überwaldean Land Eels" collection should have 2 active members

    # Check that both users can leave their respective collections.
    When I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Überwaldean Land Eels?"
    And I should see the text "By leaving the collection you will be no longer able to publish content in it and to receive notifications."
    And I should see the text "In any case, you will continue to have access to all the Collection's content and whenever you want, you will be able to rejoin the collection."
    And I should not see the link "Leave this collection"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Überwaldean Land Eels."
    And I should see the "Join this collection" button
    And the "Überwaldean Land Eels" collection should have 1 active member

    When I am logged in as "Madame Sharn"
    And I go to the homepage of the "Überwaldean Land Eels" collection
    And I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Überwaldean Land Eels?"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Überwaldean Land Eels."
    And I should see the "Join this collection" button
    And the "Überwaldean Land Eels" collection should have 0 active members

    # @todo It currently is not possible to cancel a pending membership, so for
    #   the moment we approve the membership and then leave the collection as a
    #   normal member. When ISAICP-3658 is implemented this should be replaced
    #   with a test for the cancellation of a pending membership.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3658
    Given my membership state in the "Folk Dance and Song Society" collection changes to "active"
    And I go to the homepage of the "Folk Dance and Song Society" collection
    And I click "Leave this collection"
    Then I should see the text "Are you sure you want to leave the Folk Dance and Song Society?"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Folk Dance and Song Society."
    And I should see the "Join this collection" button
    And the "Folk Dance and Song Society" collection should have 0 active members
    And the "Folk Dance and Song Society" collection should have 0 pending members

  Scenario: A collection owner leaving the collection cannot administer users anymore.

    Given users:
      | Username            |
      | insect researcher   |
      | newcomer researcher |
    And the following collection:
      | title  | Insectarium       |
      | state  | validated         |
      | author | insect researcher |
    And the following collection user memberships:
      | collection  | user                | roles |
      | Insectarium | insect researcher   | owner |
      | Insectarium | newcomer researcher |       |

    Given I am logged in as "insect researcher"
    When I go to the homepage of the "Insectarium" collection
    And I click "Leave this collection"

    # The collection owner cannot leave the collection before transferring the rights to another owner.
    Then I should see the text "You are owner of this collection. Before you leave this collection, you should transfer the ownership to another member."
    And I should not see the button "Confirm"

    When I go to the homepage of the "Insectarium" collection
    And I click "Members"
    And I select the "newcomer researcher" row
    And I select "Transfer the ownership of the collection to the selected member" from "Action"
    And I press "Apply to selected items"
    And I press "Confirm"
    Then I should see "Ownership of Insectarium collection transferred from user insect researcher to newcomer researcher."

    When I go to the homepage of the "Insectarium" collection
    And I click "Leave this collection"
    And I press "Confirm"
    When I click "Members"
    Then I should not see the link "Add members"
