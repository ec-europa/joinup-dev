@api
Feature: Navigation menu for custom pages
  In order to determine the order and visibility of custom pages in the navigation menu
  As a collection facilitator
  I need to be able to manage the navigation menu

  Scenario: Access the navigation menu through the contextual link
    Given the following collection:
      | title  | Rainbow tables |
      | logo   | logo.png       |

    When I am logged in as a facilitator of the "Rainbow tables" collection
    And I go to the homepage of the "Rainbow tables" collection
    And I click the contextual link "Edit menu" in the "Left sidebar" region
    Then I should see the heading "Edit navigation menu of the Rainbow tables collection"
    # The 'Add link' local action that is present in the default implementation
    # of OG Menu should not be visible. We are managing the menu links behind
    # the scenes. The end user should not interact with these.
    And I should not see the link "Add link"

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

  @javascript
  Scenario: The contextual link in the navigation menu should be always visible
    # In order to easily discover that I can manage the items in the navigation menu
    # As a collection facilitator
    # I should see a button in the navigation menu that displays options when clicked
    Given the following collection:
      | title  | Prism Gazers |
      | logo   | logo.png     |
    When I am logged in as a facilitator of the "Prism Gazers" collection
    And I go to the homepage of the "Prism Gazers" collection
    Then I should see the contextual links button in the "Navigation menu"
    # The links to manage the navigation menu should only appear after clicking on the button.
    And the "Edit menu" link in the "Navigation menu" should not be visible
    And the "Add new page" link in the "Navigation menu" should not be visible
    # Click the button, now the links appear.
    When I click the contextual links button in the "Navigation menu"
    Then the "Edit menu" link in the "Navigation menu" should be visible
    And the "Add new page" link in the "Navigation menu" should be visible
    # Click the button a second time to hide the links again.
    When I click the contextual links button in the "Navigation menu"
    Then the "Edit menu" link in the "Navigation menu" should not be visible
    And the "Add new page" link in the "Navigation menu" should not be visible
