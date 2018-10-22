@api
Feature: As a moderator, in order to maintain the support dropdown, I am able to
  administer the 'support' menu.

  Scenario Outline: Moderators are not to admin menus except the 'support' menu.

    Given I am an anonymous user
    When I go to "/admin/structure/menu/manage/<menu>"
    Then I should see the error message "Access denied. You must sign in to view this page."

    Given I am logged in as an "authenticated user"
    When I go to "/admin/structure/menu/manage/<menu>"
    Then I should get an access denied error

    Given I am logged in as a moderator
    When I go to "/admin/structure/menu/manage/<menu>"
    Then the response status code should be <code>
    # The moderator cannot edit the menu itself but only the menu links.
    And the following fields should not be present "Title,Administrative summary,Menu language"
    But I should <add link> the link "Add link"

    Examples:
      | menu    | code | add link |
      | admin   | 403  | not see  |
      | footer  | 403  | not see  |
      | main    | 403  | not see  |
      | support | 200  | see      |
      | tools   | 403  | not see  |
      | account | 403  | not see  |
