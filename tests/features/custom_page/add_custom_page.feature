@api
Feature: "Add custom page" visibility options.
  In order to manage custom pages
  As a collection member
  I need to be able to add "Custom page" content through UI.

  Scenario: "Add custom page" button should only be shown to moderators.
    Given the following collection:
      | name   | Code Camp          |
      | uri    | https://code.ca/mp |
      | logo   | logo.png           |

    When I am logged in as a "moderator"
    And I go to the homepage of the "Code Camp" collection
    Then I should see the link "Add custom page"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Code Camp" collection
    Then I should not see the link "Add custom page"

    When I am an anonymous user
    And I go to the homepage of the "Code Camp" collection
    Then I should not see the link "Add custom page"

  Scenario: Add custom page as a moderator.
    Given the following collection:
      | name   | Open Collective              |
      | uri    | irc://opencollective.io/?a=1 |
      | logo   | logo.png                     |
    And I am logged in as a moderator

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
