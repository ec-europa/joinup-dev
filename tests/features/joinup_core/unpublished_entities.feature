@api @terms
Feature: Unpublished content of the website
  In order to manage unpublished entities
  As a user of the website
  I want to be able to find unpublished content that I can work on

  Scenario: Test unpublished entities interaction.
    Given the following owner:
      | name            | type                    |
      | Owner something | Non-Profit Organisation |
    And users:
      | Username       | Roles |
      | Ed Abbott      |       |
      | Preston Fields |       |
      | Brenda Day     |       |
      | Phillip Shaw   |       |
    And the following collections:
      | title               | description         | state     | elibrary creation | moderation | abstract     | policy domain     | owner           |
      | Invisible Boyfriend | Invisible Boyfriend | validated | members           | no         | Trusted host | Supplier exchange | Owner something |
      | Grey Swords         | Invisible Boyfriend | proposed  | members           | no         | Trusted host | Supplier exchange | Owner something |
      | Nothing of Slaves   | Invisible Boyfriend | draft     | members           | no         | Trusted host | Supplier exchange | Owner something |
    And the following collection user memberships:
      | collection          | user           | roles         |
      | Invisible Boyfriend | Ed Abbott      | authenticated |
      | Invisible Boyfriend | Preston Fields | authenticated |
      | Invisible Boyfriend | Phillip Shaw   | facilitator   |
    And "event" content:
      | title                           | author    | collection          | state     |
      | The Ragged Streams              | Ed Abbott | Invisible Boyfriend | proposed  |
      | Storms of Touch                 | Ed Abbott | Invisible Boyfriend | validated |
      | The Male of the Gift            | Ed Abbott | Invisible Boyfriend | validated |
      | Mists in the Thought            | Ed Abbott | Invisible Boyfriend | draft     |
      | Mists that are published maybe? | Ed Abbott | Grey Swords         | validated |

    # The owner should be able to see all content.
    When I am logged in as "Ed Abbott"
    And I go to the "Invisible Boyfriend" collection
    Then I should see the "The Ragged Streams" tile
    And I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    And I should see the "Mists in the Thought" tile

    # The facilitator should not be able to see content that only have a draft state.
    When I am logged in as "Phillip Shaw"
    And I go to the "Invisible Boyfriend" collection
    Then I should see the "The Ragged Streams" tile
    And I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    But I should not see the "Mists in the Thought" tile

    # The author should be able to see all his content in his profile.
    When I am logged in as "Ed Abbott"
    And I visit "/user"
    Then I should see the "The Ragged Streams" tile
    And I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    And I should see the "Mists in the Thought" tile

    # The moderator should see the proposed collections on his dashboard.
    When I am logged in as a moderator
    And I go to the dashboard
    Then I should see the "Grey Swords" tile
    But I should not see the "Invisible Boyfriend" tile
    And I should not see the "Nothing of Slaves" tile

    # Other members should only see the published items.
    When I am logged in as "Preston Fields"
    And I go to the "Invisible Boyfriend" collection
    Then I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    But I should not see the "The Ragged Streams" tile
    And I should not see the "Mists in the Thought" tile

    # Other authenticated users should only see the published items.
    When I am logged in as "Brenda Day"
    And I go to the "Invisible Boyfriend" collection
    Then I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    But I should not see the "The Ragged Streams" tile
    And I should not see the "Mists in the Thought" tile

    # Regression test: Ensure that if there is an entity with both published and unpublished versions
    # normal users cannot access the unpublished version.
    When I am logged in as "Phillip Shaw"
    And I go to the "The Male of the Gift" event
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "The Gift of the Female"
    And I fill in "Description" with "Some random description"
    And I fill in "Location" with "Somewhere"
    And I fill in "Motivation" with "Some regression issues"
    And I press "Request changes"
    And I go to the homepage of the "Invisible Boyfriend" collection
    Then I should see the "The Male of the Gift" tile
    And I should see the "The Gift of the Female" tile
    When I am logged in as "Preston Fields"
    And I go to the "Invisible Boyfriend" collection
    Then I should see the "The Male of the Gift" tile
    But I should not see the "The Gift of the Female" tile

    # Publishing a parent should update the index of the children as well.
    When I am logged in as a moderator
    And I go to the homepage of the "Grey Swords" collection
    And I click the contextual link "Edit" in the Header region
    And I press "Publish"
    Then I should see the heading "Grey Swords"

    # An anonymous user should see the even in the newly saved version.
    When I am not logged in
    And I go to the homepage of the "Grey Swords" collection
    Then I should see the heading "Grey Swords"
    And I should not see the following tiles in the "Unpublished content area" region:
      | Mists that are published maybe? |
    But I should see the "Mists that are published maybe?" tile
