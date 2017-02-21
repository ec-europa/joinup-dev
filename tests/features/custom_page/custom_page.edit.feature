@api
Feature: "Custom page" editing.
  In order to manage custom pages
  As a moderator or collection facilitator
  I need to be able to edit "Custom Page" content through UI.

  Background:
    Given users:
      | name         | mail                     |
      | Mickey Mouse | mickey.mouse@example.com |
      | Pluto        | pluto@example.com        |
    And the following collection:
      | title       | Dumbo Collective                                                            |
      | description | Featuring a semi-anthropomorphic elephant who is cruelly nicknamed "Dumbo". |
      | logo        | logo.png                                                                    |
      | state       | validated                                                                   |
    And the following collection user memberships:
      | collection       | user         | roles       |
      | Dumbo Collective | Mickey Mouse | facilitator |
      | Dumbo Collective | Pluto        | member      |
    And "custom_page" content:
      | title                            | collection       | body                                                                                                                      |
      | Buena Vista Distribution Company | Dumbo Collective | Established in 1953, the unit handles distribution, marketing and promotion for films produced by the Walt Disney Studios |

  Scenario: Check visibility of edit button.
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

  Scenario: Edit custom page as a collection member.
    When I am logged in as "Mickey Mouse"
    And I go to the "Buena Vista Distribution Company" custom page
    And I click "Edit"
    Then I should see the heading "Edit Custom page Buena Vista Distribution Company"
    And the following fields should be present "Title, Body"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in "Title" with "Walt Disney Studios Motion Pictures"
    And I press "Save"
    Then I should have a "Custom page" content page titled "Walt Disney Studios Motion Pictures"
