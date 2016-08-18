@api
Feature: "Add custom page" visibility options.
  In order to manage custom pages
  As a collection member
  I need to be able to add "Custom page" content through UI.

  Scenario: "Add custom page" button should not be shown to normal members, authenticated users and anonymous users.
    Given the following collection:
      | title  | Code Camp |
      | logo   | logo.png  |

    When I am logged in as a member of the "Code Camp" collection
    And I go to the homepage of the "Code Camp" collection
    Then I should not see the link "Add custom page"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Code Camp" collection
    Then I should not see the link "Add custom page"

    When I am an anonymous user
    And I go to the homepage of the "Code Camp" collection
    Then I should not see the link "Add custom page"

  Scenario: Add custom page as a facilitator.
    Given collections:
      | title           | logo     |
      | Open Collective | logo.png |
      | Code Camp       | logo.png |
    And I am logged in as a facilitator of the "Open Collective" collection

    When I go to the homepage of the "Open Collective" collection
    And I click "Add custom page"
    Then I should see the heading "Add custom page"
    And the following fields should be present "Title, Body"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in the following:
      | Title | About us                      |
      | Body  | We are open about everything! |
    And I press "Save"
    Then I should see the heading "About us"
    And I should see the success message "Custom page About us has been created."
    And the "Open Collective" collection has a custom page titled "About us"
    # Check that the link to the custom page is visible on the collection page.
    When I go to the homepage of the "Open Collective" collection
    And I click "About us"
    # Check that the collection content such as the 'Join collection block' is
    # available in context of the custom page.
    Then I should see the link "Leave this collection"

    # I should not be able to add a custom page to a different collection
    When I go to the homepage of the "Code Camp" collection
    Then I should not see the link "Add custom page"

  Scenario: Add custom page as a moderator.
    Given users:
      | name    | roles     |
      | Falstad | moderator |
    And collections:
      | title           | logo     |
      | Open Collective | logo.png |
      | Code Camp       | logo.png |
    And collection user memberships:
      | collection      | user    | roles  |
      | Open Collective | Falstad | member |

    # Moderators can add custom pages in any collection, whether they are a member or not.
    Given I am logged in as "Falstad"
    When I go to the homepage of the "Open Collective" collection
    Then I should see the link "Add custom page"

    When I go to the homepage of the "Code Camp" collection
    Then I should see the link "Add custom page"
