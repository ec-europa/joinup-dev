@api @group-f
Feature: "Custom page" editing.
  In order to manage custom pages
  As a moderator or group facilitator
  I need to be able to edit "Custom Page" content through UI.

  Scenario Outline: Privileged users can edit custom pages.
    Given users:
      | Username     | E-mail                   |
      | Mickey Mouse | mickey.mouse@example.com |
      | Pluto        | pluto@example.com        |
    And the following <group>:
      | title | Dumbo Collective |
      | state | validated        |
    And the following <group> user memberships:
      | <group>          | user         | roles       |
      | Dumbo Collective | Mickey Mouse | facilitator |
      | Dumbo Collective | Pluto        | member      |
    And "custom_page" content:
      | title                            | <group>          | body | logo     |
      | Buena Vista Distribution Company | Dumbo Collective | N/A  | logo.png |

    # Group owner should see the button.
    When I am logged in as "Mickey Mouse"
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should see the link "Edit"
    # A normal member cannot edit custom pages, so they don't see the button.
    When I am logged in as "Pluto"
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should not see the link "Edit"
    # A moderator can edit all custom pages.
    When I am logged in as a user with the moderator role
    And I go to the "Buena Vista Distribution Company" custom page
    # Moderators have the 'administer nodes' permission.
    Then I should see the link "Edit"
    # A normal logged in user should not be able to edit the custom page.
    When I am logged in as a user with the authenticated role
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should not see the link "Edit"
    # An anonymous user cannot edit the custom page.
    When I am logged in as a user with the authenticated role
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should not see the link "Edit"

    # Edit the page as a facilitator.
    When I am logged in as "Mickey Mouse"
    And I go to the "Buena Vista Distribution Company" custom page
    And I click "Edit"
    Then I should see the heading "Edit Custom page Buena Vista Distribution Company"
    And the following fields should be present "Title, Body, Published"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in "Title" with "Walt Disney Studios Motion Pictures"
    And I press "Save"
    Then I should have a "Custom page" content page titled "Walt Disney Studios Motion Pictures"

    Examples:
      | group      |
      | collection |
      | solution   |
