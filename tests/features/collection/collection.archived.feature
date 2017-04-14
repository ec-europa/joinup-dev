@api @terms
Feature: Creating content and commenting on archived collection
  In order to not waste time on maintaining legacy collections
  As a collection owner
  I want to be able to archive old collections

  Background: Check access to the Post comment form
    Given users:
      | Username    | Roles     |
      | Flora Hunt  | moderator |
      | Lee Reeves  |           |
      | Karl Fields |           |
    And the following contact:
      | email | JodyRodriquez@bar.com |
      | name  | Jody Rodriquez        |
    And the following owner:
      | name          |
      | April Hawkins |
    And the following collections:
      | title               | description         | logo     | banner     | owner         | contact information | state     | policy domain           |
      | The Willing Consort | The Willing Consort | logo.png | banner.jpg | April Hawkins | Jody Rodriquez      | validated | Statistics and Analysis |
    And the following collection user memberships:
      | collection          | user        | roles              |
      | The Willing Consort | Karl Fields | owner, facilitator |

  Scenario: 'Comment form' should not be accessible on an archived collection content.
    Given discussion content:
      | title               | collection          | state     |
      | The Weeping's Stars | The Willing Consort | validated |
    When I am logged in as "Lee Reeves"
    And I go to the "The Weeping's Stars" discussion
    Then the following fields should be present "Comment"
    And I should see the button "Post comment"

    When I am not logged in
    And I go to the "The Weeping's Stars" discussion
    Then the following fields should be present "Your name, Email, Comment"
    And I should see the button "Post comment"

    When I am logged in as "Karl Fields"
    And I go to the "The Willing Consort" collection
    And I click "Edit"
    And I press "Request archival"
    And I am logged in as a moderator
    And I go to the "The Willing Consort" collection
    And I click "Edit"
    And I press "Archive"
    And I go to the "The Weeping's Stars" discussion
    Then the following fields should not be present "Comment"
    And I should not see the button "Post comment"

    When I am logged in as "Lee Reeves"
    And I go to the "The Weeping's Stars" discussion
    Then the following fields should not be present "Comment"
    And I should not see the button "Post comment"

    When I am not logged in
    And I go to the "The Weeping's Stars" discussion
    Then the following fields should not be present "Your name, Email, Comment"
    And I should not see the button "Post comment"

  Scenario: 'Add community content' menu items should not be visible in the archived connection.
    When I am logged in as "Karl Fields"
    And I go to the "The Willing Consort" collection
    And I click "Edit"
    And I press "Request archival"
    And I am logged in as a moderator
    And I go to the "The Willing Consort" collection
    And I click "Edit"
    And I press "Archive"

    # We only need to check that privileged users do not have access anymore.
    And I am logged in as a facilitator of the "The Willing Consort" collection
    And I go to the "The Willing Consort" collection
    Then I should not see the contextual link "Add event" in the "Plus button menu" region
    And I should not see the contextual link "Add news" in the "Plus button menu" region
    And I should not see the contextual link "Add document" in the "Plus button menu" region
    And I should not see the contextual link "Add discussion" in the "Plus button menu" region
    And I should not see the contextual link "Add custom page" in the "Navigation menu block" region
