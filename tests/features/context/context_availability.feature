@api
Feature: Context availability
  In order to check context availability
  As a user
  I need to be able to see collection content in child entities pages.

  Scenario: 'Join collection' and 'Collection content' block availability.
    Given the following collection:
      | title             | Context example collection |
      | logo              | logo.png                   |
      | moderation        | yes                        |
      | closed            | yes                        |
      | elibrary creation | facilitators               |

    # The manual process is followed so that the og hooks can properly fire
    # otherwise the content will not be created as a logged in user and
    # the functionality breaks.
    And I am logged in as a moderator
    When I go to the homepage of the "Context example collection" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    And the following fields should be present "Title, Body"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in the following:
      | Title | About context                      |
      | Body  | We are open about everything! |
    And I press "Save"
    # In the collection page, both blocks should be visible.
    When I go to the homepage of the "Context example collection" collection
    Then I should see the heading "Collection content"
    And I should see the "Join this collection" button
    And I should see the link "About context"
    # In the custom page, only the 'Join collection' block should be available.
    When I click "About context"
    Then I should not see the heading "Collection content"
    And I should see the "Join this collection" button