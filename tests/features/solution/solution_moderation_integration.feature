@api @group-f
Feature: As a user of the website
  I want to be able to perform available transitions
  according to the state of the entity and the graph they are stored in.

  @terms
  Scenario: Check availability of actions depending on the state and the graph.
    Given users:
      | Username        | Roles     |
      | Hulk            |           |
      | Captain America | moderator |
    And the following contact:
      | email | crabbypatties@bar.com |
      | name  | Crusty crab           |
    And the following owner:
      | name    | type                  |
      | Mr Crab | Private Individual(s) |
    And the following solutions:
      | title                | description                | logo     | banner     | owner   | contact information | solution type | state        | topic       |
      | Professional Dreams  | Azure ship                 | logo.png | banner.jpg | Mr Crab | Crusty crab         | Business      | draft        | E-inclusion |
      | The Falling Swords   | The Falling Swords         | logo.png | banner.jpg | Mr Crab | Crusty crab         | Business      | proposed     | E-inclusion |
      | Flight of Night      | Rose of Doors              | logo.png | banner.jpg | Mr Crab | Crusty crab         | Business      | validated    | E-inclusion |
      | Teacher in the Twins | The Guardian of the Stream | logo.png | banner.jpg | Mr Crab | Crusty crab         | Business      | needs update | E-inclusion |
      | Missing Fire         | Flames in the Swords       | logo.png | banner.jpg | Mr Crab | Crusty crab         | Business      | blacklisted  | E-inclusion |
    And the following solution user memberships:
      | solution        | user | roles |
      | Flight of Night | Hulk | owner |
    When I am logged in as a "facilitator" of the "Professional Dreams" solution
    And I go to the homepage of the "Professional Dreams" solution
    Then I should see the heading "Professional Dreams"
    And I should see the link "View draft"
    # @todo: Fix the visibility issue.
    And I should see the link "View"
    But I should not see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |
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
    And I should not see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |

    # Edit as facilitator and save as draft.
    When I click "Edit"
    Then the current workflow state should be "Validated"
    When I fill in "Title" with "Flight of Day"
    And I press "Save as draft"

    # The page redirects to the canonical view after editing.
    Then I should see the heading "Flight of Night"
    And I should not see the heading "Flight of Day"
    And I should see the link "View draft"
    And I should see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |
    When I click "View draft"
    # The header still shows the published title but the draft title is included
    # in the page.
    Then I should see the heading "Flight of Day"
    But I should not see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |

    # Ensure that the message is not shown to non privileged users.
    When I am an anonymous user
    And I go to the homepage of the "Flight of Night" solution
    And I should not see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |

    # Publish draft version of the solution.
    When I am logged in as a moderator
    And I go to the homepage of the "Flight of Day" solution
    And I click "Edit"
    Then the current workflow state should be "Draft"
    When I press "Publish"
    Then I should see the heading "Flight of Day"
    And I should not see the link "View draft"
    But I should see the link "View"

    # Ensure that the users do not lose their membership.
    When I am logged in as "Hulk"
    And I go to the homepage of the "Flight of Day" solution
    Then I should not see the link "View Draft"
    But I should see the link "Edit" in the "Entity actions" region
