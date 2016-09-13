@api
Feature: Change this

  Scenario: All the tests are here?
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

    # I should not be able to view draft solutions I'm not a facilitator of.
    When I go to the homepage of the "The Falling Swords" solution
    Then I should see the heading "Access denied"

    When I am logged in as a "facilitator" of the "Flight of Night" solution
    And I go to the homepage of the "Flight of Night" solution
    Then I should see the heading "Flight of Night"
    # Since it's only in draft, the normal view is the draft view
    # and the "View draft should not be shown.
    And I should not see the link "View Draft"

    # Edit as facilitator and save as draft.
    When I click "Edit"
    And I fill in "Title" with "Flight of Day"
    And I select "Draft" from "State"
    And I press "Save"
    And I should see the heading "Flight of Day"