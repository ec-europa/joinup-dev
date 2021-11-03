@api @terms @group-d
Feature: Unpublished content of the website
  In order to manage unpublished entities
  As a user of the website
  I want to be able to find unpublished content that I can work on

  Scenario: Interact with unpublished entities in collections.
    Given the following owner:
      | name            | type                    |
      | Owner something | Non-Profit Organisation |
    And the following contact:
      | name  | Published contact       |
      | email | pub.contact@example.com |
    And users:
      | Username       | Roles |
      | Ed Abbott      |       |
      | Preston Fields |       |
      | Brenda Day     |       |
      | Phillip Shaw   |       |
    And the following collections:
      | title               | description         | state     | content creation | moderation | abstract     | topic             | owner           | contact information |
      | Invisible Boyfriend | Invisible Boyfriend | validated | members          | no         | Trusted host | Supplier exchange | Owner something | Published contact   |
      | Grey Swords         | Invisible Boyfriend | proposed  | members          | no         | Trusted host | Supplier exchange | Owner something | Published contact   |
      | Nothing of Slaves   | Invisible Boyfriend | draft     | members          | no         | Trusted host | Supplier exchange | Owner something | Published contact   |
    And the following collection user memberships:
      | collection          | user           | roles         |
      | Invisible Boyfriend | Ed Abbott      | authenticated |
      | Invisible Boyfriend | Preston Fields | authenticated |
      | Invisible Boyfriend | Phillip Shaw   | facilitator   |
    And "event" content:
      | title                                 | created           | author    | collection          | state     |
      | The Ragged Streams                    | 2018-10-04 8:31am | Ed Abbott | Invisible Boyfriend | proposed  |
      | Storms of Touch                       | 2018-10-04 8:31am | Ed Abbott | Invisible Boyfriend | validated |
      | The Male of the Gift                  | 2018-10-04 8:31am | Ed Abbott | Invisible Boyfriend | validated |
      | Mists in the Thought                  | 2018-10-04 8:31am | Ed Abbott | Invisible Boyfriend | draft     |
      | Mists outside the planes of thinking  | 2018-10-04 8:30am | Ed Abbott | Grey Swords         | draft     |
      | Mists outside the planes of construct | 2018-10-04 8:31am | Ed Abbott | Grey Swords         | draft     |
      | Mists that are published maybe?       | 2018-10-04 8:31am | Ed Abbott | Grey Swords         | validated |
    And glossary content:
      | title    | synonyms | summary                 | author    | created           | definition                                  | collection          | status      |
      | Alphabet | ABC      | Summary of Alphabet     | Ed Abbott | 2018-10-04 8:29am | Long, long definition field                 | Invisible Boyfriend | published   |
      | Colors   | CLR      | Summary of Colors       | Ed Abbott | 2018-10-04 8:29am | Colors definition field                     | Invisible Boyfriend | unpublished |
      | Smells   | SML      | Smells Like Teen Spirit | Ed Abbott | 2018-10-04 8:31am | With the lights out, it's less dangerous... | Invisible Boyfriend | unpublished |

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
    Then I should see the following tiles in the correct order:
      # Published content appears first in the content listing. Note: Published
      # glossary terms are not indexed, so 'Alphabet' will not appear.
      | Invisible Boyfriend                   |
      | Storms of Touch                       |
      | The Male of the Gift                  |
      # Unpublished content.
      | The Ragged Streams                    |
      | Mists in the Thought                  |
      | Mists outside the planes of thinking  |
      | Mists outside the planes of construct |
      | Mists that are published maybe?       |
      | Colors                                |
      | Smells                                |

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
    And I fill in "Physical location" with "Somewhere"
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
    When I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then I should see the heading "Grey Swords"

    # An anonymous user should see the even in the newly saved version.
    When I am not logged in
    And I go to the homepage of the "Grey Swords" collection
    Then I should see the heading "Grey Swords"
    And I should not see the following tiles in the "Unpublished content area" region:
      | Mists that are published maybe? |
    But I should see the "Mists that are published maybe?" tile

    # Unpublished content is ordered by creation date.
    When I am logged in as "Ed Abbott"
    And I go to the homepage of the "Grey Swords" collection
    And I should see the following tiles in the correct order:
      # Published content appears first in the content listing.
      | Mists that are published maybe?       |
      # Created at 8:31am.
      | Mists outside the planes of construct |
      # Created at 8:30am.
      | Mists outside the planes of thinking  |

  Scenario: Interact with unpublished entities in solutions.
    Given the following owner:
      | name            | type                    |
      | Owner something | Non-Profit Organisation |
    And the following contact:
      | name  | Published contact       |
      | email | pub.contact@example.com |
    And users:
      | Username      |
      | Ed Abbott     |
      | Facilitator 1 |
      | Facilitator 2 |
    And the following solutions:
      | title               | description         | state     | owner           | contact information |
      | Invisible Boyfriend | Invisible Boyfriend | validated | Owner something | Published contact   |
    And the following solution user memberships:
      | solution            | user          | roles         |
      | Invisible Boyfriend | Ed Abbott     | authenticated |
      | Invisible Boyfriend | Facilitator 1 | facilitator   |
      | Invisible Boyfriend | Facilitator 2 | facilitator   |
    And "event" content:
      | title                | created           | author    | solution            | state     |
      | The Ragged Streams   | 2018-10-04 8:31am | Ed Abbott | Invisible Boyfriend | proposed  |
      | Storms of Touch      | 2018-10-04 8:31am | Ed Abbott | Invisible Boyfriend | validated |
      | The Male of the Gift | 2018-10-04 8:31am | Ed Abbott | Invisible Boyfriend | validated |
      | Mists in the Thought | 2018-10-04 8:31am | Ed Abbott | Invisible Boyfriend | draft     |

    # Create a release as a facilitator. In contrast to community content, only
    # facilitators are allowed to create releases.
    When I am logged in as "Facilitator 1"
    When I go to the homepage of the "Invisible Boyfriend" solution
    And I click "Add release"
    When I fill in "Name" with "Chasing shadows"
    And I fill in "Release number" with "1.0"
    And I fill in "Release notes" with "Changed release."
    And I press "Save as draft"
    Then I should see the heading "Chasing shadows 1.0"

    # As a facilitator, I should see my own unpublished content, and content
    # that is proposed by other members and is awaiting publication.
    When I go to the "Invisible Boyfriend" solution
    And I should see the following tiles in the "Unpublished content area" region:
      | Chasing shadows    |
      | The Ragged Streams |

    # The entity should be visible in the user's account page as well.
    When I click "My account"
    And I should see the following tiles in the "My unpublished content area" region:
      | Chasing shadows |

    # Other facilitators should also see draft releases and proposed entities
    # in the "Unpublished content" region, but they should not see other content
    # in draft state. The reason for this is that facilitators should only be
    # aware of content that is proposed / ready for publication. Releases are an
    # exception because these are facilitator-only and always OK to show.
    When I am logged in as "Facilitator 2"
    And I go to the "Invisible Boyfriend" solution
    And I should see the following tiles in the "Unpublished content area" region:
      | Chasing shadows    |
      | The Ragged Streams |
    But I should not see the "Mists in the Thought" tile
    # The unpublished release created by the other facilitator should not show
    # up in the user's account page.
    When I click "My account"
    Then I should not see the "Chasing shadows" tile

    # A user should be able to see all content that they created. regardless of
    # the state.
    When I am logged in as "Ed Abbott"
    And I go to the "Invisible Boyfriend" solution
    Then I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    And I should see the following tiles in the "Unpublished content area" region:
      | The Ragged Streams   |
      | Mists in the Thought |
    # The user should not see unpublished content that they didn't create.
    But I should not see the following tiles in the "Unpublished content area" region:
      | Chasing shadows |

    # The unpublished entities should be visible in the user's account page.
    When I click "My account"
    And I should see the following tiles in the "My unpublished content area" region:
      | The Ragged Streams   |
      | Mists in the Thought |
    But I should not see the following tiles in the "My unpublished content area" region:
      | Chasing shadows |

    # Other authenticated users should only see the published items.
    When I am logged in as a user with the "authenticated" role
    And I go to the "Invisible Boyfriend" solution
    Then I should see the "Storms of Touch" tile
    And I should see the "The Male of the Gift" tile
    But I should not see the "The Ragged Streams" tile
    And I should not see the "Mists in the Thought" tile
    And I should not see the following tiles in the "Unpublished content area" region:
      | Chasing shadows |

    # Publish the release. It should now be moved to the main section and no
    # longer show up in the unpublished content area.
    Given I am logged in as "Facilitator 1"
    And I go to the "Chasing shadows" release
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    And I go to the "Invisible Boyfriend" solution
    Then I should see the "Chasing shadows" tile
    But I should not see the following tiles in the "Unpublished content area" region:
      | Chasing shadows |
