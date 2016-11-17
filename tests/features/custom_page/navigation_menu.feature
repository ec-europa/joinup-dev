@api
Feature: Navigation menu for custom pages
  In order to determine the order and visibility of custom pages in the navigation menu
  As a collection facilitator
  I need to be able to manage the navigation menu

  Scenario: Access the navigation menu through the contextual link
    Given the following collection:
      | title | Rainbow tables |
      | logo  | logo.png       |
      | state | validated      |

    # Initially there are no items in the navigation menu, instead we see a help
    # text inviting the user to add a page to the menu.
    When I am logged in as a facilitator of the "Rainbow tables" collection
    And I go to the homepage of the "Rainbow tables" collection
    Then the navigation menu of the "Rainbow tables" collection should have 0 visible items
    And I should see the text "There are no pages yet. Why don't you start by creating an About page?"
    And I should see the link "Add a new page"
    # Check that the 'Edit menu' local action is not present.
    But I should not see the contextual link "Edit menu" in the "Left sidebar" region
    # The 'Add link' local action that is present in the default implementation
    # of OG Menu should not be visible. We are managing the menu links behind
    # the scenes. The end user should not be able to interact with these.
    And I should not see the contextual link "Add link" in the "Left sidebar" region

    # When we create a custom page it should automatically show up in the menu.
    When I click "Add a new page"
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | About us              |
      | Body  | A short introduction. |
    And I press "Save"
    Then I should see the success message "Custom page About us has been created."
    And the navigation menu of the "Rainbow tables" collection should have 1 visible item
    And I should not see the text "There are no custom pages yet."

    When I click the contextual link "Edit menu" in the "Left sidebar" region
    Then the navigation menu of the "Rainbow tables" collection should have 1 item
    But I should not see the text "There are no custom pages yet."

    # It should be possible to hide an item from the menu by disabling it.
    When I disable "About us" in the navigation menu of the "Rainbow tables" collection
    Then the navigation menu of the "Rainbow tables" collection should have 0 visible items

    # The form to add a new menu link should not be accessible by anyone. This
    # is functionality provided by Drupal which is intended for webmasters. We
    # are showing the menu overview to collection facilitators so they can
    # reorder the navigation menu, but they should not be able to access the
    # related menu administration screens.
    And I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" collection

    # Members of the collection should not have access to the administration
    # pages either.
    Given I am logged in as a member of the "Rainbow tables" collection
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" collection

    # Even moderators should not have access to the administration pages.
    Given I am logged in as a moderator
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" collection

    # Anonymous users should definitely not have access.
    Given I am an anonymous user
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" collection
