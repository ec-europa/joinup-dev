@api
Feature: "Custom page" editing.
  In order to manage custom pages
  # @todo Change this to use OG Roles once they are available. (Collection owner)
  As a moderator
  I need to be able to edit "Custom Page" content through UI.

  Background:
    Given users:
      | name         | mail                     | roles     |
      | Mickey Mouse | mickey.mouse@example.com | Moderator |
    And the following collections:
      | uri                               | name                 | description                                                                            | logo     |
      | http://joinup.eu/disney/dumbo     | Dumbo Collection     | Featuring a semi-anthropomorphic elephant who is cruelly nicknamed "Dumbo".            | logo.png |
      | http://joinup.eu/disney/pinocchio | Pinocchio Collection | Featuring an old wood-carver named Geppetto who carves a wooden puppet named Pinocchio | logo.png |
    And the following user memberships:
      | collection       | user         |
      | Dumbo Collection | Mickey Mouse |
    And "custom_page" content:
      | title                            | og_group_ref                  | body                                                                                                                      |
      | Buena Vista Distribution Company | http://joinup.eu/disney/dumbo | Established in 1953, the unit handles distribution, marketing and promotion for films produced by the Walt Disney Studios |

  Scenario: Check visibility of edit button.
    When I am logged in as "Mickey Mouse"
    And I go to the "Buena Vista Distribution Company" custom page
    Then I should see the link "Edit"
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
