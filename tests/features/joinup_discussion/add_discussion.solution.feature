@api @group-d
Feature: "Add discussion" visibility options.
  In order to manage discussions
  As a solution member
  I need to be able to add "Discussion" content through UI.

  Scenario: "Add discussion" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following collection:
      | title  | Collective Eager Sliver |
      | logo   | logo.png                |
      | banner | banner.jpg              |
      | state  | validated               |
    And the following solutions:
      | title              | collection              | logo     | banner     | state     |
      | Eager Sliver       | Collective Eager Sliver | logo.png | banner.jpg | validated |
      | The Silent Bridges | Collective Eager Sliver | logo.png | banner.jpg | validated |

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

  @terms @uploadFiles:test.zip
  Scenario: Add discussion as a facilitator.
    Given the following collection:
      | title  | Collective Emerald in the Luck |
      | logo   | logo.png                       |
      | banner | banner.jpg                     |
      | state  | validated                      |
    And the following solutions:
      | title               | collection                     | logo     | banner     | state     |
      | Emerald in the Luck | Collective Emerald in the Luck | logo.png | banner.jpg | validated |
    And I am logged in as a facilitator of the "Emerald in the Luck" solution

    When I go to the homepage of the "Emerald in the Luck" solution
    And I click "Add discussion" in the plus button menu
    Then I should see the heading "Add discussion"
    And the following fields should be present "Title, Content, Topic, Add a new file"
    And the following fields should not be present "Motivation"

    # Check required fields.
    And I attach the file "test.zip" to "Add a new file"
    And I press "Upload"
    And I press "Publish"
    Then I should see the following lines of text:
      | Title field is required.                       |
      | The Attachments field description is required. |
      | Content field is required.                     |

    When I fill in the following:
      | Title            | Flight of Girlfriend                       |
      | Content          | This is going to be an amazing discussion. |
      | File description | A picture of a flying girlfriend           |
    And I press "Publish"
    Then I should see the error message "Topic field is required."

    And I select "EU and European Policies" from "Topic"
    And I press "Publish"

    Then I should see the heading "Flight of Girlfriend"
    And I should see the success message "Discussion Flight of Girlfriend has been created."
    And the "Emerald in the Luck" solution has a discussion titled "Flight of Girlfriend"
    # Check that the link to the discussion is visible on the solution page.
    When I go to the homepage of the "Emerald in the Luck" solution
    Then I should see the link "Flight of Girlfriend"

    # Check that an anonymous user can see the information.
    Given I am an anonymous user
    When I go to the "Flight of Girlfriend" discussion
    Then I should see the following headings:
      | Emerald in the Luck  |
      | Flight of Girlfriend |
    And I should see the following lines of text:
      | This is going to be an amazing discussion. |
      | Attachments                                |
      | 176 bytes                                  |
    And I should see the link "A picture of a flying girlfriend"
