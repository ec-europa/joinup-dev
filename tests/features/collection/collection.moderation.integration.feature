@api @terms @group-d
Feature: As a user of the website
  I want to be able to perform available transitions
  according to the state of the entity and the graph they are stored in.

  Scenario: Check availability of actions depending on the state and the graph.
    Given users:
      | Username    | Roles     |
      | Cornelius   |           |
      | Tina Butler | moderator |
    And the following contact:
      | email | martykelley@bar.com |
      | name  | Marty Kelley        |
    And the following owner:
      | name            |
      | Martin Gonzalez |
    And the following collections:
      | title                | description          | logo     | banner     | owner           | contact information | state            | topic                   |
      | Willing Fairy        | Willing Fairy        | logo.png | banner.jpg | Martin Gonzalez | Marty Kelley        | draft            | Statistics and Analysis |
      | The Fallen Thoughts  | The Fallen Thoughts  | logo.png | banner.jpg | Martin Gonzalez | Marty Kelley        | proposed         | Finance in EU           |
      | Destruction of Scent | Destruction of Scent | logo.png | banner.jpg | Martin Gonzalez | Marty Kelley        | validated        | Supplier exchange       |
      | The School's Stars   | The School's Stars   | logo.png | banner.jpg | Martin Gonzalez | Marty Kelley        | archival request | E-justice               |
      | Boy in the Dreams    | Boy in the Dreams    | logo.png | banner.jpg | Martin Gonzalez | Marty Kelley        | archived         | E-health                |
    And the following collection user memberships:
      | collection           | user      | roles              |
      | Destruction of Scent | Cornelius | owner, facilitator |
    When I am logged in as a "facilitator" of the "Willing Fairy" collection
    And I go to the homepage of the "Willing Fairy" collection
    Then I should see the heading "Willing Fairy"
    And I should see the link "View draft"
    And I should see the link "View"
    But I should not see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |
    # @todo: Fix the visibility issue.
    And I should see the link "Edit" in the "Entity actions" region

    # I should not be able to view draft collections I'm not a facilitator of.
    When I go to the homepage of the "The Fallen Thoughts" collection
    Then I should see the heading "Access denied"

    When I am logged in as a "facilitator" of the "Destruction of Scent" collection
    And I go to the homepage of the "Destruction of Scent" collection
    Then I should see the heading "Destruction of Scent"
    # Since it's validated, the normal view is the published view and the
    # "View draft" should not be shown.
    And I should not see the link "View Draft"
    And I should not see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |


    # Edit as facilitator and save as draft.
    When I click "Edit"
    Then the following fields should be present "Current workflow state"
    And the current workflow state should be "Validated"
    When I fill in "Title" with "Construction of Scent"
    And I press "Save as draft"

    # The page redirects to the canonical view after editing.
    Then I should see the heading "Destruction of Scent"
    And I should not see the heading "Construction of Scent"
    And I should see the link "View draft"
    And I should see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |
    When I click "View draft"
    # The header still shows the published title but the draft title is included
    # in the page.
    Then I should see the heading "Construction of Scent"

    # Ensure that the message is not shown to non privileged users.
    When I am an anonymous user
    And I go to the homepage of the "Destruction of Scent" collection
    And I should not see the following warning messages:
      | warning messages                                                                     |
      | You are viewing the published version. To view the latest draft version, click here. |

    # Publish draft version of the collection.
    When I am logged in as a moderator
    And I go to the homepage of the "Construction of Scent" collection
    And I click "Edit"
    Then the current workflow state should be "Draft"
    When I press "Publish"
    Then I should see the heading "Construction of Scent"
    And I should not see the link "View draft"
    But I should see the link "View"

    # Ensure that the users do not lose their membership.
    When I am logged in as "Cornelius"
    And I go to the homepage of the "Construction of Scent" collection
    Then I should not see the link "View Draft"
    But I should see the link "Edit" in the "Entity actions" region
