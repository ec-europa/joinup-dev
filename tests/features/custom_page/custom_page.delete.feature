@api @group-f
Feature: "Custom page" deleteing.
  In order to manage custom pages
  As a moderator or group facilitator
  I need to be able to delete "Custom Page" content through UI.

  Scenario Outline: Check visibility of delete button.
    Given users:
      | Username     | E-mail                   |
      | Mickey Mouse | mickey.mouse@example.com |
      | Pluto        | pluto@example.com        |
    And the following <group>:
      | title       | Dumbo Collective                                                            |
      | description | Featuring a semi-anthropomorphic elephant who is cruelly nicknamed "Dumbo". |
      | logo        | logo.png                                                                    |
      | state       | validated                                                                   |
    And the following <group> user memberships:
      | <group>          | user         | roles       |
      | Dumbo Collective | Mickey Mouse | facilitator |
      | Dumbo Collective | Pluto        | member      |
    And "custom_page" content:
      | title                            | <group>          | body                                                                                                                      |
      | Buena Vista Distribution Company | Dumbo Collective | Established in 1953, the unit handles distribution, marketing and promotion for films produced by the Walt Disney Studios |

    # Group owner should see the button.
    When I am logged in as "Mickey Mouse"
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should see the link "Delete"
    # A normal member cannot delete custom pages, so they don't see the button.
    When I am logged in as "Pluto"
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should not see the link "Delete"
    # A moderator can delete all custom pages.
    When I am logged in as a user with the moderator role
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should see the link "Delete"
    # A normal logged in user should not be able to delete the custom page.
    When I am logged in as a user with the authenticated role
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should not see the link "Delete"
    # An anonymous user cannot delete the custom page.
    When I am logged in as a user with the authenticated role
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should not see the link "Delete"

    # Delete the custom page as a facilitator.
    When I am logged in as "Mickey Mouse"
    And I go to the "Buena Vista Distribution Company" custom page
    And I click "Delete"
    Then I should see the heading "Are you sure you want to delete the content item Buena Vista Distribution Company?"
    And I press "Delete"
    Then I should see the success message "The Custom page Buena Vista Distribution Company has been deleted."

    Examples:
      | group      |
      | collection |
      | solution   |
