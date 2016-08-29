@api
Feature: Collection edited by owner
  In order to manage my collections
  As the collection facilitator
  I need to be able to edit my collection but not other collections.

  # A collection owner also has permission to edit a collection because he is also a facilitator.
  # A user that creates a collection is automatically an owner.
  Scenario: A collection facilitator should be able to edit only his collection.
    Given the following person:
      | name | Foo |
    And collections:
      | title         | description                           | logo     | banner     | owner | moderation | policy domain |
      | Wendy's house | Let's build a house for Wendy.        | logo.png | banner.jpg | Foo   | yes        | Health        |
      | Mayor's house | We cannot build a house for Mr. Mayor | logo.png | banner.jpg | Foo   | yes        | Health        |
    When I am logged in as a "facilitator" of the "Wendy's house" collection
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
