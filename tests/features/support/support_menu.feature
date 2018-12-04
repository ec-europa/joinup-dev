@api
Feature:
  - As a moderator, in order to maintain the support dropdown, I am able to
    administer the 'support' menu.
  - As a user, I should see the the 'Take a tour' menu item only on pages that
    are implementing tours.
  - As a moderator I can add custom menu items and disable default menu items.

  Scenario Outline: Moderators are not able to admin menus except support menu.

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

  Scenario Outline: Test user support menu usage.

    Given the following collections:
      | title            | state     |
      | Hotel California | validated |

    Given I am <role>

    When I am on the homepage
    Then I should see the link "Take a tour"
    And I should see the link "Contact support"

    When I go to "/collections"
    Then I should not see the link "Take a tour"
    But I should see the link "Contact support"

    When I go to "/keep-up-to-date"
    Then I should see the link "Take a tour"
    And I should see the link "Contact support"

    When I go to the homepage of the "Hotel California" collection
    Then I should see the link "Take a tour"
    And I should see the link "Contact support"

    When I go to "/user"
    Then I should <expectation> the link "Take a tour"
    And I should see the link "Contact support"

    Examples:
      | role                                 | expectation |
      | an anonymous user                    | not see     |
      | logged in as an "authenticated user" | see         |

  Scenario: A moderator is able to administer the user support menu.

    Given I am logged in as a moderator
    When I go to "/admin/structure/menu/manage/support"
    And I uncheck the "Take a tour" row
    And I press "Save"

    Given I click "Add link"
    When I fill in "Menu link title" with "Arbitrary support menu link"
    And I fill in "Link" with "http://example.com"
    And I press "Save"
    Then I should see the link "Arbitrary support menu link"

    When I am on the homepage
    Then I should see the link "Contact support"
    And I should see "Arbitrary support menu link"
    # Tour has been disabled.
    But I should not see the link "Take a tour"

    # Restore the tour menu link.
    Given I go to "/admin/structure/menu/manage/support"
    And I check the "Take a tour" row
    And I press "Save"

    # Reset the tour menu link.
    When I click "Reset" in the "Take a tour" row
    Then I should see the heading "Are you sure you want to reset the link Take a tour to its default values?"
    When I press "Reset"
    Then I should not see the link "Reset"

    When I click "Delete" in the "Arbitrary support menu link" row
    Then I should see the heading "Are you sure you want to delete the custom menu link Arbitrary support menu link?"

    When I press "Delete"
    Then I should see the success message "The menu link Arbitrary support menu link has been deleted."
    And I should not see the link "Arbitrary support menu link"

    When I am on the homepage
    Then I should see the link "Contact support"
    # The custom link has been deleted.
    But I should not see "Arbitrary support menu link"
    # Tour has been re-enabled.
    And I should see the link "Take a tour"
