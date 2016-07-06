@api
Feature: Collection edited by owner
  In order to manage my collections
  As the collection owner
  I need to be able to edit my collection but not other collections.

  # A collection owner does not actually have permission to edit a collection.
  # The permission derive from the facilitator role. A user that creates a
  # a collection is automatically an owner and a facilitator.
  Scenario: Owner of a collection should be able to edit only his collection.
    Given users:
      | name            | pass          | mail                        |
      | Bob the builder | bobthebuilder | bob.the.builder@example.com |
    And collections:
      | title         | description                           | logo     | moderation |
      | Wendy's house | Let's build a house for Wendy.        | logo.png | yes        |
      | Mayor's house | We cannot build a house for Mr. Mayor | logo.png | yes        |
    And user memberships:
      | collection    | user            | roles                      |
      # Assigning the default roles as when we create a collection through UI.
      | Wendy's house | Bob the builder | owner, facilitator, member |
    When I am logged in as "Bob the builder"
    And I go to the homepage of the "Wendy's house" collection
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should see the heading "Edit Collection Wendy's house"
    When I fill in "Title" with "Wendy's house is fixed"
    And I press "Save"
    And I should see the heading "Wendy's house is fixed"

    # Check that the owner cannot edit another collection.
    When I go to the homepage of the "Mayor's house" collection
    Then I should not see the "Edit" button
