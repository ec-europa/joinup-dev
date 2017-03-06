@api
Feature: "Add discussion" visibility options.
  In order to manage discussions
  As a solution member
  I need to be able to add "Discussion" content through UI.

  Scenario: "Add discussion" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following solutions:
      | title              | logo     | banner     | state     |
      | Eager Sliver       | logo.png | banner.jpg | validated |
      | The Silent Bridges | logo.png | banner.jpg | validated |
    And the following collection:
      | title      | Collective Eager Sliver          |
      | logo       | logo.png                         |
      | banner     | banner.jpg                       |
      | affiliates | Eager Sliver, The Silent Bridges |
      | state      | validated                        |

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Eager Sliver" solution
    Then I should not see the link "Add discussion"

    When I am an anonymous user
    And I go to the homepage of the "Eager Sliver" solution
    Then I should not see the link "Add discussion"

    When I am logged in as a "facilitator" of the "Eager Sliver" solution
    And I go to the homepage of the "Eager Sliver" solution
    Then I should see the link "Add discussion"
    # I should not be able to add a discussion to a different solution
    When I go to the homepage of the "The Silent Bridges" solution
    Then I should not see the link "Add discussion"

    When I am logged in as a "moderator"
    And I go to the homepage of the "Eager Sliver" solution
    Then I should see the link "Add discussion"

  Scenario: Add discussion as a facilitator.
    Given solutions:
      | title               | logo     | banner     | state     |
      | Emerald in the Luck | logo.png | banner.jpg | validated |
    And the following collection:
      | title      | Collective Emerald in the Luck |
      | logo       | logo.png                       |
      | banner     | banner.jpg                     |
      | affiliates | Emerald in the Luck            |
      | state      | validated                      |
    And I am logged in as a facilitator of the "Emerald in the Luck" solution

    When I go to the homepage of the "Emerald in the Luck" solution
    And I click "Add discussion"
    Then I should see the heading "Add discussion"
    And the following fields should be present "Title, Content, Policy domain"

    # The sections about managing revisions and groups should not be visible.
    And I should not see the text "Revision information"
    And the following fields should not be present "Groups audience, Other groups, Create new revision, Revision log message"

    When I fill in the following:
      | Title   | Flight of Girlfriend                       |
      | Content | This is going to be an amazing discussion. |
    And I press "Publish"
    Then I should see the heading "Flight of Girlfriend"
    And I should see the success message "Discussion Flight of Girlfriend has been created."
    And the "Emerald in the Luck" solution has a discussion titled "Flight of Girlfriend"
    # Check that the link to the discussion is visible on the solution page.
    When I go to the homepage of the "Emerald in the Luck" solution
    Then I should see the link "Flight of Girlfriend"
