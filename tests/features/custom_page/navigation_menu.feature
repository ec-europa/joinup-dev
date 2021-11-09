@api @group-a
Feature: Navigation menu for custom pages
  In order to determine the order and visibility of custom pages in the navigation menu
  As a facilitator
  I need to be able to manage the navigation menu

  Scenario Outline: Access the navigation menu through the contextual link
    Given the following <group>s:
      | title            | logo     | state     |
      | Rainbow tables   | logo.png | validated |
      | Cripple Mr Onion | logo.png | validated |

    # By default, a link to the collection canonical page and a link to the
    # about page are added to the menu.
    When I am logged in as a facilitator of the "Rainbow tables" <group>
    And I go to the homepage of the "Rainbow tables" <group>
    Then the navigation menu of the "Rainbow tables" <group> should have <visible items 1> visible items
    And I should see the following group menu items in the specified order:
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
    And the navigation menu of the "Rainbow tables" <group> should have <visible items 2> visible items

    When I click the contextual link "Edit menu" in the "Left sidebar" region
    Then the navigation menu of the "Rainbow tables" <group> should have 5 items

    # It should be possible to hide an item from the menu by disabling it.
    When I disable "About us" in the navigation menu of the "Rainbow tables" <group>
    Then the navigation menu of the "Rainbow tables" <group> should have <visible items 3> visible items

    # When all the pages are disabled in the navigation menu, a message should
    # be shown to the user.
    When I disable "Overview" in the navigation menu of the "Rainbow tables" <group>
    And I disable "Members" in the navigation menu of the "Rainbow tables" <group>
    And I disable "About" in the navigation menu of the "Rainbow tables" <group>
    And I go to the homepage of the "Rainbow tables" <group>
    Then I should see the text "All the pages have been disabled for this <group>. You can edit the menu configuration or add a new page."
    And I should see the contextual link "Edit menu" in the "Left sidebar" region

    # The contextual menu can be used to navigate to the menu edit page.
    When I click the contextual link "Edit menu" in the "Left sidebar" region
    Then I should see the heading "Edit navigation menu of the Rainbow tables <group>"

    # The form to add a new menu link should not be accessible by anyone. This
    # is functionality provided by Drupal which is intended for webmasters. We
    # are showing the menu overview to collection facilitators so they can
    # reorder the navigation menu, but they should not be able to access the
    # related menu administration screens.
    And I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" <group>

    # Enable the menu entry for the 'About us' page again so we can check if it
    # is visible for all users.
    When I enable "About us" in the navigation menu of the "Rainbow tables" <group>

    # Create a few custom pages in the second collection so we can check if the
    # right menu shows up in each collection.
    Given custom_page content:
      | title           | body                                                                                                                                  | <group>          |
      | Eights are wild | You cannot Cripple Mr Onion if your running flush contains more wild eights than the Lesser or Great Onion you are trying to cripple. | Cripple Mr Onion |
      | Eights are null | They can be included in an existing Onion in order to improve its size by one card.                                                   | Cripple Mr Onion |

    # Test as a normal member of the group.
    Given I am logged in as a member of the "Rainbow tables" <group>
    When I go to the homepage of the "Rainbow tables" <group>
    # Members of the collection should not have access to the administration
    # pages.
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" <group>
    # The navigation link from the current group should be visible, but not
    # the link from the second collection.
    And I should see the link "About us" in the "Navigation menu"
    But I should not see the link "Eights are wild" in the "Navigation menu"
    And I should not see the link "Eights are null" in the "Navigation menu"
    # Test the navigation link of the second collection.
    When I go to the homepage of the "Cripple Mr Onion" <group>
    Then I should see the link "Eights are wild" in the "Navigation menu"
    And I should see the link "Eights are null" in the "Navigation menu"
    But I should not see the link "About us" in the "Navigation menu"

    # Test as a moderator.
    Given I am logged in as a moderator
    When I go to the homepage of the "Rainbow tables" <group>
    # Even moderators should not have access to the administration pages.
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" <group>
    # The navigation link from the current group should be visible, but not
    # the link from the second group.
    And I should see the link "About us" in the "Navigation menu"
    But I should not see the link "Eights are wild" in the "Navigation menu"
    And I should not see the link "Eights are null" in the "Navigation menu"
    # Test the navigation link of the second collection.
    When I go to the homepage of the "Cripple Mr Onion" <group>
    Then I should see the link "Eights are wild" in the "Navigation menu"
    And I should see the link "Eights are null" in the "Navigation menu"
    But I should not see the link "About us" in the "Navigation menu"

    # Test as an anonymous user.
    Given I am an anonymous user
    When I go to the homepage of the "Rainbow tables" <group>
    # Anonymous users should definitely not have access to the administration
    # pages.
    Then I should not have access to the menu link administration pages for the navigation menu of the "Rainbow tables" <group>

    # The navigation link from the current collection should be visible, but not
    # the link from the second group.
    And I should see the link "About us" in the "Navigation menu"
    But I should not see the link "Eights are wild" in the "Navigation menu"
    And I should not see the link "Eights are null" in the "Navigation menu"
    # Test the navigation link of the second group.
    When I go to the homepage of the "Cripple Mr Onion" <group>
    Then I should see the link "Eights are wild" in the "Navigation menu"
    And I should see the link "Eights are null" in the "Navigation menu"
    But I should not see the link "About us" in the "Navigation menu"

    Examples:
      | group      | visible items 1 | visible items 2 | visible items 3 |
      | collection | 4               | 5               | 4               |
      | solution   | 3               | 4               | 3               |

  @javascript
  Scenario Outline: The contextual links button in the navigation menu should be always visible
    # In order to easily discover that I can manage the items in the navigation menu
    # As a group facilitator
    # I should see a button in the navigation menu that displays options when clicked
    Given the following <group>:
      | title | Prism Gazers |
      | logo  | logo.png     |
      | state | validated    |
    And custom_page content:
      | title           | body                   | <group>      |
      | Mists of dreams | This is a sample body. | Prism Gazers |
    When I am logged in as a facilitator of the "Prism Gazers" <group>
    And I go to the homepage of the "Prism Gazers" <group>
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

    Examples:
      | group      |
      | collection |
      | solution   |

  Scenario Outline: Synchronize titles of custom pages and menu links
    Given the following <group>:
      | title | Ravenous wood-munching alphabeavers |
      | state | validated                           |
    And custom_page content:
      | title       | body                                                                | <group>                             |
      | Tree eaters | Given time, they will most likely strip the entire region of trees. | Ravenous wood-munching alphabeavers |
    When I am logged in as a facilitator of the "Ravenous wood-munching alphabeavers" "<group>"
    And I go to the homepage of the "Ravenous wood-munching alphabeavers" <group>
    Then I should see the link "Tree eaters" in the "Navigation menu"

    # Change the title and check that the link is updated.
    When I click "Tree eaters"
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "An army of furry little killing machines"
    And I press "Save"
    And I go to the homepage of the "Ravenous wood-munching alphabeavers" <group>
    Then I should see the link "An army of furry little killing machines" in the "Navigation menu"
    And I should not see the link "Tree eaters" in the "Navigation menu"

    Examples:
      | group      |
      | collection |
      | solution   |

  Scenario Outline: Only the links below the topmost page are rendered in TOC.
    Given the following <group>s:
      | title                   | state     |
      | Table of contents group | validated |
    And custom_page content:
      | title         | <group>                 |
      | Page 1        | Table of contents group |
      | Page 2        | Table of contents group |
      | Page 3        | Table of contents group |
      | Subpage 1-1   | Table of contents group |
      | Subpage 1-1-1 | Table of contents group |
      | Subpage 1-2   | Table of contents group |
      | Subpage 1-2-1 | Table of contents group |
      | Subpage 1-2-2 | Table of contents group |
      | Subpage 2-1   | Table of contents group |
    And the following custom page menu structure:
      | title         | parent      | weight |
      | Page 1        |             | 1      |
      | Page 2        |             | 2      |
      | Page 3        |             | 3      |
      | Subpage 1-1   | Page 1      | 1      |
      | Subpage 1-1-1 | Subpage 1-1 | 1      |
      | Subpage 1-2   | Page 1      | 2      |
      | Subpage 1-2-1 | Subpage 1-2 | 1      |
      | Subpage 1-2-2 | Subpage 1-2 | 2      |
      | Subpage 2-1   | Page 2      | 1      |

    When I am logged in as a member of the "Table of contents group" <group>
    And I go to the homepage of the "Table of contents group" <group>
    Then I should not see the "Table of contents" region

    # Only the sub-page links of "Page 1" are rendered.
    When I visit the "Page 1" custom page
    Then I should see the link "Subpage 1-1" in the "Table of contents"
    And I should see the link "Subpage 1-2" in the "Table of contents"
    But I should not see the link "Page 1" in the "Table of contents"
    And I should not see the link "Page 2" in the "Table of contents"
    And I should not see the link "Subpage 2-1" in the "Table of contents"
    And I should not see the link "Page 3" in the "Table of contents"
    And I should see the link "Subpage 1-1-1" in the "Table of contents"
    And I should see the link "Subpage 1-2-1" in the "Table of contents"
    And I should see the link "Subpage 1-2-2" in the "Table of contents"

    # Visit "Subpage 1-1" to verify that appropriate children are expanded.
    When I visit the "Subpage 1-1" custom page
    Then I should see the link "Subpage 1-1" in the "Table of contents"
    And I should see the link "Subpage 1-1-1" in the "Table of contents"
    And I should see the link "Subpage 1-2" in the "Table of contents"
    But I should not see the link "Page 1" in the "Table of contents"
    And I should not see the link "Page 2" in the "Table of contents"
    And I should not see the link "Subpage 2-1" in the "Table of contents"
    And I should not see the link "Page 3" in the "Table of contents"
    And I should see the link "Subpage 1-2-1" in the "Table of contents"
    And I should see the link "Subpage 1-2-2" in the "Table of contents"

    # Visit "Subpage 1-2" to verify that appropriate children are expanded.
    When I visit the "Subpage 1-2" custom page
    Then I should see the link "Subpage 1-1" in the "Table of contents"
    And I should see the link "Subpage 1-2" in the "Table of contents"
    And I should see the link "Subpage 1-2-1" in the "Table of contents"
    And I should see the link "Subpage 1-2-2" in the "Table of contents"
    But I should not see the link "Page 1" in the "Table of contents"
    And I should not see the link "Page 2" in the "Table of contents"
    And I should not see the link "Subpage 2-1" in the "Table of contents"
    And I should not see the link "Page 3" in the "Table of contents"
    And I should see the link "Subpage 1-1-1" in the "Table of contents"

    # Ensure that the default links are not shown.
    And I should not see the link "Overview" in the "Table of contents"
    And I should not see the link "Members" in the "Table of contents"
    And I should not see the link "About" in the "Table of contents"
    But I should see the link "Overview" in the "Navigation menu block"
    And I should see the link "Members" in the "Navigation menu block"
    And I should see the link "About" in the "Navigation menu block"

    # Only the sub-page links of "Page 2" are rendered.
    When I visit the "Page 2" custom page
    Then I should see the link "Subpage 2-1" in the "Table of contents"
    But I should not see the link "Page 2" in the "Table of contents"
    And I should not see the link "Page 1" in the "Table of contents"
    And I should not see the link "Subpage 1-1" in the "Table of contents"
    And I should not see the link "Subpage 1-2" in the "Table of contents"

    # On a custom page without sub-pages there's no table of contents.
    When I visit the "Page 3" custom page
    Then I should not see the "Table of contents" region

    # Verify that the menu only shows on the canonical route.
    When I am logged in as a moderator
    And I visit the "Page 1" custom page
    And I click "Edit" in the "Entity actions" region
    Then I should not see the "Table of contents" region

    Examples:
      | group      |
      | collection |
      | solution   |

  @javascript
  Scenario Outline: Only custom page entries can be nested in the navigation menu.
    Given the following <group>:
      | title | Ergonomic backpacks |
      | state | validated           |
    And custom_page content:
      | title              | <group>             | status    |
      | Types of backpacks | Ergonomic backpacks | published |
      | Frameless          | Ergonomic backpacks | published |
      | External frame     | Ergonomic backpacks | published |
      | Internal frame     | Ergonomic backpacks | published |
      | Bodypack           | Ergonomic backpacks | published |
    And the following custom pages menu structure:
      | title              | parent             | weight |
      | Types of backpacks |                    | 3      |
      | Frameless          | Types of backpacks | 4      |
      | External frame     | Types of backpacks | 5      |
      | Internal frame     |                    | 6      |
      # Force a reserved page to be nested. This is not possible through the UI.
      | About              | Bodypack           | 7      |
    When I am logged in as a facilitator of the "Ergonomic backpacks" <group>
    And I go to the "Ergonomic backpacks" <group>
    And I click the contextual link "Edit menu" in the "Left sidebar" region
    # The "About" page has been moved back to first level.
    Then the draggable menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Glossary           |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     | Types of backpacks |
      | Internal frame     |                    |
    When I drag the "External frame" table row to the left
    Then the draggable menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Glossary           |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     |                    |
    When I drag the "Internal frame" table row to the right
    Then the draggable menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Glossary           |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
    # Links that don't refer to a node cannot be nested.
    When I drag the "Members" table row to the right
    And I drag the "About" table row to the right
    And I drag the "Glossary" table row to the right
    Then the draggable menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Glossary           |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
    # Nor cannot have child rows.
    When I drag the "Bodypack" table row to the right
    Then the draggable menu table should be:
      | title              | parent             |
      | Overview           |                    |
      | Members            |                    |
      | Glossary           |                    |
      | Bodypack           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
    # But they can still be re-ordered up and down.
    When I drag the "Overview" table row down
    When I drag the "About" table row up
    And I drag the "Glossary" table row up
    Then the draggable menu table should be:
      | title              | parent             |
      | Members            |                    |
      | Glossary           |                    |
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
    Then the draggable menu table should be:
      | title              | parent             |
      | Members            |                    |
      | Glossary           |                    |
      | Overview           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | Bodypack           | Types of backpacks |
      | External frame     |                    |
      | Internal frame     | External frame     |
    # Maximum indentation level is two.
    When I drag the "Bodypack" table row to the right
    And I drag the "Internal frame" table row to the left
    And I drag the "Internal frame" table row up
    And I drag the "Internal frame" table row to the right
    And I drag the "Internal frame" table row to the right
    Then the draggable menu table should be:
      | title              | parent             |
      | Members            |                    |
      | Glossary           |                    |
      | Overview           |                    |
      | About              |                    |
      | Types of backpacks |                    |
      | Frameless          | Types of backpacks |
      | Bodypack           | Frameless          |
      | Internal frame     | Frameless          |
      | External frame     |                    |

    Examples:
      | group      |
      | collection |
      | solution   |

  Scenario Outline: Show appropriate menu entries in the table of contents outline.
    Given the following <group>s:
      | title                     | state     |
      | Table of contents outline | validated |
    And custom_page content:
      | title      | <group>                   | status      |
      | TOCO 1     | Table of contents outline | published   |
      | TOCO 2     | Table of contents outline | published   |
      | TOCO 1-1   | Table of contents outline | published   |
      | TOCO 1-1-1 | Table of contents outline | published   |
      | TOCO 2-1   | Table of contents outline | published   |
      | TOCO 1-1-2 | Table of contents outline | published   |
      | TOCO 1-2   | Table of contents outline | published   |
      | TOCO 2-1-2 | Table of contents outline | unpublished |
      | TOCO 2-1-1 | Table of contents outline | published   |
    And the following custom page menu structure:
      | title      | parent   | weight |
      | TOCO 1-2   | TOCO 1   | 2      |
      | TOCO 1-1-2 | TOCO 1-1 | 2      |
      | TOCO 2-1   | TOCO 2   | 1      |
      | TOCO 1     |          | 1      |
      | TOCO 1-1-1 | TOCO 1-1 | 1      |
      | TOCO 2     |          | 2      |
      | TOCO 2-1-2 | TOCO 2-1 | 1      |
      | TOCO 1-1   | TOCO 1   | 1      |
      | TOCO 2-1-1 | TOCO 2-1 | 1      |

    When I am logged in as a member of the "Table of contents outline" <group>
    And I go to the homepage of the "Table of contents outline" <group>
    Then I should not see the "Table of contents outline" region

    When I visit the "TOCO 1" custom page
    Then I should not see the link "Up" in the "Table of contents outline"
    # This link does not exist because the outline does not contain connection to the default links just as TOC does.
    And I should not see the link "About" in the "Table of contents outline"

    # Navigate through the outline. Most links are asserted by clicking on them.
    When I click "TOCO 1-1" in the "Table of contents outline"
    Then I should see the link "TOCO 1" in the "Table of contents outline"
    And I should see the link "TOCO 1-1-1" in the "Table of contents outline"
    And I should see the link "Up" in the "Table of contents outline"
    And I click "TOCO 1-1-1" in the "Table of contents outline"
    And I click "TOCO 1-1-2" in the "Table of contents outline"
    And I click "TOCO 1-2" in the "Table of contents outline"
    And I should not see the link "TOCO 2" in the "Table of contents outline"

    When I visit the "TOCO 2" custom page
    Then I click "TOCO 2-1" in the "Table of contents outline"

    And I click "TOCO 2-1-1" in the "Table of contents outline"
    Then I should not see the link "TOCO 2-1-2" in the "Table of contents outline"
    # Navigate backwards.
    And I click "TOCO 2-1" in the "Table of contents outline"
    And I click "TOCO 2" in the "Table of contents outline"
    And I should not see the link "TOCO 1-2" in the "Table of contents outline"

    When I visit the "TOCO 1-2" custom page
    And I click "TOCO 1-1-2" in the "Table of contents outline"
    And I click "TOCO 1-1-1" in the "Table of contents outline"
    And I click "TOCO 1-1" in the "Table of contents outline"
    And I click "TOCO 1" in the "Table of contents outline"
    # Navigate through the "Up" link.
    When I visit the "TOCO 2" custom page
    Then I should not see the link "Up" in the "Table of contents outline"
    When I visit the "TOCO 1-2" custom page
    And I click "Up" in the "Table of contents outline"
    Then I should see the heading "TOCO 1"
    When I visit the "TOCO 1-1-2" custom page
    And I click "Up" in the "Table of contents outline"
    Then I should see the heading "TOCO 1-1"

    # Verify that the menu only shows on the canonical route.
    When I am logged in as a moderator
    And I visit the "TOCO 1" custom page
    And I click "Edit" in the "Entity actions" region
    Then I should not see the "Table of contents outline" region

    When I disable "TOCO 1-1-2" in the navigation menu of the "Table of contents outline" <group>
    And I visit the "TOCO 1-1-1" custom page
    Then I should not see the link "TOCO 1-1-2" in the "Table of contents outline"

    Examples:
      | group      |
      | collection |
      | solution   |

  @javascript
  Scenario Outline: Assert cache invalidation of the TOC outline.
    Given the following <group>s:
      | title                            | state     |
      | Table of contents outline cached | validated |
    And custom_page content:
      | title           | <group>                          | status    |
      | TOCO cached 1   | Table of contents outline cached | published |
      | TOCO cached 1-1 | Table of contents outline cached | published |
    And the following custom page menu structure:
      | title           | parent        | weight |
      | TOCO cached 1   |               | 1      |
      | TOCO cached 1-1 | TOCO cached 1 | 1      |
    When I visit the "TOCO cached 1-1" custom page
    Then I should see the link "Up" in the "Table of contents outline"

    When I am logged in as a facilitator of the "Table of contents outline cached" <group>
    And I go to the "Table of contents outline cached" <group>
    And I click the contextual link "Edit menu" in the "Left sidebar" region
    When I drag the "TOCO cached 1-1" table row to the left
    Then the draggable menu table should be:
      | title           | parent |
      | Overview        |        |
      | Members         |        |
      | About           |        |
      | Glossary        |        |
      | TOCO cached 1   |        |
      | TOCO cached 1-1 |        |
    When I press "Save"
    And I visit the "TOCO cached 1-1" custom page
    Then I should not see the link "Up" in the "Table of contents outline"

    Examples:
      | group      |
      | collection |
      | solution   |

  Scenario Outline: Test that the edit link appears next to the "About" page.
    Given the following <group>s:
      | title           | state     |
      | About edit link | validated |

    When I am logged in as a moderator
    And I go to the "About edit link" <group>
    And I click the contextual link "Edit menu" in the "Left sidebar" region

    Then the "group menu edit table" table should contain the following columns:
      | Page     | Enabled | Operations |
      | Overview |         | Edit       |
      | Members  |         |            |
      | About    |         | Edit       |
    When I click "Edit" in the "Content" region
    Then I should see the heading "Edit <label> About edit link"

    Examples:
      | group      | label      |
      | collection | Collection |
      | solution   | Solution   |
