@api @terms
Feature: Commenting on archived collection
  In order to prevent unwanted commenting
  As a site moderator
  I want to prevent people from commenting on content of an archived collection.

  Scenario: Check access to the
    Given users:
      | name        | roles     |
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
    And discussion content:
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
    # 'Administer comments' permission give access even in archived collections.
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
