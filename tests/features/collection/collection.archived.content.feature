@api @terms
Feature: Creating content on archived collection
  In order for archived collections to work properly
  As a site moderator
  I want to prevent people from creating content of an archived collection.

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
