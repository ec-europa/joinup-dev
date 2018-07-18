@api
Feature: Navigation menu for custom pages
  In order to determine the order and visibility of custom pages in the navigation menu
  As a collection facilitator
  I need to be able to manage the navigation menu

  Scenario: Access the navigation menu through the contextual link
    Given the following collections:
      | title            | logo     | state     |
      | Rainbow tables   | logo.png | validated |
      | Cripple Mr Onion | logo.png | validated |

    # By default, a link to the collection canonical page and a link to the
    # about page are added to the menu.
    When I am logged in as a facilitator of the "Rainbow tables" collection
    And I go to the homepage of the "Rainbow tables" collection
    Then the navigation menu of the "Rainbow tables" collection should have 3 visible items
    And I should see the following collection menu items in the specified order:
      | text     |
      | Overview |
      | Members  |
      | About    |
    # Check that the 'Edit menu' local action is present.
    And I should see the contextual link "Edit menu" in the "Left sidebar" region
    # The 'Add link' local action that is present in the default implementation
    # of OG Menu should not be visible. We are managing the menu links behind
    # the scenes. The end user should not be able to interact with these.
    But I should not see the contextual link "Add link" in the "Left sidebar" region

    # When we create a custom page it should automatically show up in the menu.
    When I click the contextual link "Add new page" in the "Left sidebar" region
    Then I should see the heading "Add custom page"
    When I fill in the following:
      | Title | About us |
    And I enter "A short introduction." in the "Body" wysiwyg editor
    And I press "Save"
    Then I should see the success message "Custom page About us has been created."
    And the navigation menu of the "Rainbow tables" collection should have 4 visible items

    When I click the contextual link "Edit menu" in the "Left sidebar" region
    Then the navigation menu of the "Rainbow tables" collection should have 4 items

    # It should be possible to hide an item from the menu by disabling it.
    When I disable "About us" in the navigation menu of the "Rainbow tables" collection
    Then the navigation menu of the "Rainbow tables" collection should have 3 visible items

    # When all the pages are disabled in the navigation menu, a message should
    # be shown to the user.
    When I disable "Overview" in the navigation menu of the "Rainbow tables" collection
    And I disable "Members" in the navigation menu of the "Rainbow tables" collection
    And I disable "About" in the navigation menu of the "Rainbow tables" collection
    And I go to the homepage of the "Rainbow tables" collection
    Then I should see the text "All the pages have been disabled for this collection. You can edit the menu configuration or add a new page."
    And I should see the contextual link "Edit menu" in the "Left sidebar" region

    # The contextual menu can be used to navigate to the menu edit page.
    When I click the contextual link "Edit menu" in the "Left sidebar" region
    Then I should see the heading "Edit navigation menu of the Rainbow tables collection"

    # The form to add a new menu link should not be accessible by anyone. This
    # is functionality provided by Drupal which is intended for webmasters. We
    # are showing the menu overview to collection facilitators so they can
    # reorder the navigation menu, but they should not be able to access the
    # related menu administration screens.
    And I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" collection

    # Enable the menu entry for the 'About us' page again so we can check if it
    # is visible for all users.
    When I enable "About us" in the navigation menu of the "Rainbow tables" collection

    # Create a few custom pages in the second collection so we can check if the
    # right menu shows up in each collection.
    Given custom_page content:
      | title           | body                                                                                                                                  | collection       |
      | Eights are wild | You cannot Cripple Mr Onion if your running flush contains more wild eights than the Lesser or Great Onion you are trying to cripple. | Cripple Mr Onion |
      | Eights are null | They can be included in an existing Onion in order to improve its size by one card.                                                   | Cripple Mr Onion |

    # Test as a normal member of the collection.
    Given I am logged in as a member of the "Rainbow tables" collection
    When I go to the homepage of the "Rainbow tables" collection
    # Members of the collection should not have access to the administration
    # pages.
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" collection
    # The navigation link from the current collection should be visible, but not
    # the link from the second collection.
    And I should see the link "About us" in the "Navigation menu"
    But I should not see the link "Eights are wild" in the "Navigation menu"
    And I should not see the link "Eights are null" in the "Navigation menu"
    # Test the navigation link of the second collection.
    When I go to the homepage of the "Cripple Mr Onion" collection
    Then I should see the link "Eights are wild" in the "Navigation menu"
    And I should see the link "Eights are null" in the "Navigation menu"
    But I should not see the link "About us" in the "Navigation menu"

    # Test as a moderator.
    Given I am logged in as a moderator
    When I go to the homepage of the "Rainbow tables" collection
    # Even moderators should not have access to the administration pages.
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" collection
    # The navigation link from the current collection should be visible, but not
    # the link from the second collection.
    And I should see the link "About us" in the "Navigation menu"
    But I should not see the link "Eights are wild" in the "Navigation menu"
    And I should not see the link "Eights are null" in the "Navigation menu"
    # Test the navigation link of the second collection.
    When I go to the homepage of the "Cripple Mr Onion" collection
    Then I should see the link "Eights are wild" in the "Navigation menu"
    And I should see the link "Eights are null" in the "Navigation menu"
    But I should not see the link "About us" in the "Navigation menu"

    # Test as an anonymous user.
    Given I am an anonymous user
    When I go to the homepage of the "Rainbow tables" collection
    # Anonymous users should definitely not have access to the administration
    # pages.
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" collection

    # The navigation link from the current collection should be visible, but not
    # the link from the second collection.
    And I should see the link "About us" in the "Navigation menu"
    But I should not see the link "Eights are wild" in the "Navigation menu"
    And I should not see the link "Eights are null" in the "Navigation menu"
    # Test the navigation link of the second collection.
    When I go to the homepage of the "Cripple Mr Onion" collection
    Then I should see the link "Eights are wild" in the "Navigation menu"
    And I should see the link "Eights are null" in the "Navigation menu"
    But I should not see the link "About us" in the "Navigation menu"

  @javascript
  Scenario: The contextual links button in the navigation menu should be always visible
    # In order to easily discover that I can manage the items in the navigation menu
    # As a collection facilitator
    # I should see a button in the navigation menu that displays options when clicked
    Given the following collection:
      | title | Prism Gazers |
      | logo  | logo.png     |
    And custom_page content:
      | title           | body                   | collection   |
      | Mists of dreams | This is a sample body. | Prism Gazers |
    When I am logged in as a facilitator of the "Prism Gazers" collection
    And I go to the homepage of the "Prism Gazers" collection
    Then I should see the contextual links button in the "Navigation menu block"
    # The links to manage the navigation menu should only appear after clicking on the button.
    And the "Edit menu" link in the "Navigation menu block" should not be visible
    And the "Add new page" link in the "Navigation menu block" should not be visible
    # Click the button, now the links appear.
    When I click the contextual links button in the "Navigation menu block"
    Then the "Edit menu" link in the "Navigation menu block" should be visible
    And the "Add new page" link in the "Navigation menu block" should be visible
    # Click the button a second time to hide the links again.
    When I click the contextual links button in the "Navigation menu block"
    Then the "Edit menu" link in the "Navigation menu block" should not be visible
    And the "Add new page" link in the "Navigation menu block" should not be visible

  Scenario: Synchronize titles of custom pages and menu links
    Given the following collection:
      | title | Ravenous wood-munching alphabeavers |
    And custom_page content:
      | title       | body                                                                | collection                          |
      | Tree eaters | Given time, they will most likely strip the entire region of trees. | Ravenous wood-munching alphabeavers |
    When I am logged in as a facilitator of the "Ravenous wood-munching alphabeavers" collection
    And I go to the homepage of the "Ravenous wood-munching alphabeavers" collection
    Then I should see the link "Tree eaters" in the "Navigation menu"

    # Change the title and check that the link is updated.
    When I click "Tree eaters"
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "An army of furry little killing machines"
    And I press "Save"
    And I go to the homepage of the "Ravenous wood-munching alphabeavers" collection
    Then I should see the link "An army of furry little killing machines" in the "Navigation menu"
    And I should not see the link "Tree eaters" in the "Navigation menu"

  Scenario: Only active links of the table of content is expanded.
    Given the following collections:
      | title                       | state     |
      | Table of content collection | validated |
    And custom_page content:
      | title           | collection                  |
      | Custom page 1   | Table of content collection |
      | Custom page 2   | Table of content collection |
      | Custom page 1-1 | Table of content collection |
      | Custom page 1-2 | Table of content collection |
      | Custom page 2-1 | Table of content collection |
    And the following custom page menu structure:
      | title           | parent        | weight |
      | Custom page 1   |               | 1      |
      | Custom page 2   |               | 2      |
      | Custom page 1-1 | Custom page 1 | 1      |
      | Custom page 1-2 | Custom page 1 | 2      |
      | Custom page 2-1 | Custom page 2 | 1      |

    When I am logged in as a member of the "Table of content collection" collection
    And I go to the homepage of the "Table of content collection" collection
    Then I see the text "Table of content" in the "Navigation bottom menu block" region
    And I should see the link "Custom page 1" in the "Navigation bottom menu block"
    And I should see the link "Custom page 2" in the "Navigation bottom menu block"
    But I should not see the link "Custom page 1-1" in the "Navigation bottom menu block"
    And I should not see the link "Custom page 1-2" in the "Navigation bottom menu block"
    And I should not see the link "Custom page 2-1" in the "Navigation bottom menu block"

    # Ensure that the default links are not shown.
    And I should not see the link "Overview" in the "Navigation bottom menu block"
    And I should not see the link "Members" in the "Navigation bottom menu block"
    And I should not see the link "About" in the "Navigation bottom menu block"
    But I should see the link "Overview" in the "Navigation menu block"
    And I should see the link "Members" in the "Navigation menu block"
    And I should see the link "About" in the "Navigation menu block"

    When I visit the "Custom page 1" custom page
    And I should see the link "Custom page 1" in the "Navigation bottom menu block"
    And I should see the link "Custom page 2" in the "Navigation bottom menu block"
    And I should see the link "Custom page 1-1" in the "Navigation bottom menu block"
    And I should see the link "Custom page 1-2" in the "Navigation bottom menu block"
    But I should not see the link "Custom page 2-1" in the "Navigation bottom menu block"

  @javascript
  Scenario: Only custom page entries can be nested in the collection navigation menu.
    Given the following collection:
      | title | Ergonomic backpacks |
      | state | validated           |
    And custom_page content:
      | title              | collection          | status    |
      | Types of backpacks | Ergonomic backpacks | published |
      | Frameless          | Ergonomic backpacks | published |
      | External frame     | Ergonomic backpacks | published |
      | Internal frame     | Ergonomic backpacks | published |
      | Bodypack           | Ergonomic backpacks | published |
    And the following collection menu structure:
      | title              | parent             | weight |
      | Types of backpacks |                    | 3      |
      | Frameless          | Types of backpacks | 4      |
      | External frame     | Types of backpacks | 5      |
      | Internal frame     |                    | 6      |
      # Force a reserved page to be nested. This is not possible through the UI.
      | About              | Bodypack           | 7      |

    When I am logged in as a facilitator of the "Ergonomic backpacks" collection
    And I go to the "Ergonomic backpacks" collection
    And I click the contextual link "Edit menu" in the "Left sidebar" region
    # The "About" page has been moved back to first level.
    Then the menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     | Types of backpacks |
      | Internal frame     |                    |
    When I drag the "External frame" table row to the left
    Then the menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     |                    |
    When I drag the "Internal frame" table row to the right
    Then the menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
    # Maximum indentation level is one.
    When I drag the "Internal frame" table row to the right
    Then the menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
    # Links that don't refer to a node cannot be nested.
    When I drag the "Members" table row to the right
    And I drag the "About" table row to the right
    Then the menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
    # But they can still be re-ordered up and down.
    When I drag the "Overview" table row down
    When I drag the "About" table row up
    Then the menu table should be:
      | title              | parent             |
      | Members            |                    |
      | Overview           |                    |
      | About              |                    |
      | Bodypack           |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
    # Links pointing to nodes can be moved too.
    When I drag the "Bodypack" table row down
    And I drag the "Bodypack" table row down
    Then the menu table should be:
      | title              | parent             |
      | Members            |                    |
      | Overview           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | Bodypack           | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
