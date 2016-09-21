@api
Feature: As a user of the website
  I want to be able to perform available transitions
  according to the state of the entity and the graph they are stored in.

  Scenario: Check availability of actions depending on the state and the graph.
    Given users:
      | name            | pass            | roles     |
      | Hulk            | big_green_puppy |           |
      | Captain America | star_shield     | moderator |
    And the following contact:
      | email | crabbypatties@bar.com |
      | name  | Crusty crab           |
    And the following organisation:
      | name | Mr Crab |

    And the following solutions:
      | title                    | description                | logo     | banner     | owner   | contact information | state            | policy domain |
      | Professional Dreams      | Azure ship                 | logo.png | banner.jpg | Mr Crab | Crusty crab         | draft            | Health        |
      | The Falling Swords       | The Falling Swords         | logo.png | banner.jpg | Mr Crab | Crusty crab         | proposed         | Health        |
      | Flight of Night          | Rose of Doors              | logo.png | banner.jpg | Mr Crab | Crusty crab         | validated        | Health        |
      | The Streams of the Lover | The Ice's Secrets          | logo.png | banner.jpg | Mr Crab | Crusty crab         | deletion_request | Health        |
      | Teacher in the Twins     | The Guardian of the Stream | logo.png | banner.jpg | Mr Crab | Crusty crab         | in_assessment    | Health        |
      | Missing Fire             | Flames in the Swords       | logo.png | banner.jpg | Mr Crab | Crusty crab         | blacklisted      | Health        |

    When I am logged in as a "facilitator" of the "Professional Dreams" solution
    And I go to the homepage of the "Professional Dreams" solution
    Then I should see the heading "Professional Dreams"
    # Since it's only in draft, the normal view is the draft view
    # and the "View draft should not be shown.
    And I should not see the link "View Draft"
    # @todo: Fix the visibility issue.
    But I should see the link "View"
    And I should see the link "Edit" in the "Entity actions" region

    # I should not be able to view draft solutions I'm not a facilitator of.
    When I go to the homepage of the "The Falling Swords" solution
    Then I should see the heading "Access denied"

    When I am logged in as a "facilitator" of the "Flight of Night" solution
    And I go to the homepage of the "Flight of Night" solution
    Then I should see the heading "Flight of Night"
    # Since it's validated, the normal view is the published view and the
    # "View draft" should not be shown.
    And I should not see the link "View Draft"

    # Edit as facilitator and save as draft.
    When I click "Edit"
    And I fill in "Title" with "Flight of Day"
    And I select "Draft" from "State"
    And I press "Save"

    # The page redirects to the canonical view after editing.
    Then I should see the heading "Flight of Night"
    And I should not see the heading "Flight of Day"
    And I should see the link "View Draft"
    When I click "View Draft"
    # The header still shows the published title but the draft title is included
    # in the page.
    Then I should see the heading "Flight of Day"

    # Publish draft version of the solution.
    When I am logged in as a moderator
    And I go to the homepage of the "Flight of Day" solution
    And I click "Edit"
    And I select "Validated" from "State"
    And I press "Save"
    Then I should see the heading "Flight of Day"
    And I should not see the link "View Draft"
    # @todo: Fix the visibility issue.
    But I should see the link "View"
