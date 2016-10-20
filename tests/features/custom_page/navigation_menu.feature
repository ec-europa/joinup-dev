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

    When I am logged in as a facilitator of the "Rainbow tables" collection
    And I go to the homepage of the "Rainbow tables" collection
    And I click the contextual link "Edit menu" in the "Left sidebar" region
    Then I should see the heading "Edit navigation menu of the Rainbow tables collection"
    # The 'Add link' local action that is present in the default implementation
    # of OG Menu should not be visible. We are managing the menu links behind
    # the scenes. The end user should not interact with these.
    And I should not see the link "Add link"

    # We should see a link to add a new custom page.
    But I should see the link "Add page"
    When I click "Add page"
    Then I should see the heading "Add custom page"

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
