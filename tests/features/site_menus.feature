Feature: Site menus
  In order to navigate through the sections of the site
  As a user
  I want to have access to site-wide menus

  @api
  Scenario: Main menu items should be active based on the current page.
    Given solution:
      | title | Rich Sound |
      | state | validated  |
    And collection:
      | title      | Hungry Firecracker |
      | state      | validated          |
      | affiliates | Rich Sound         |
    And releases:
      | title               | release number | release notes | is version of | state     |
      | Alphorn sheet music | 1              | First notes.  | Rich Sound    | validated |
    And the following distributions:
      | title          | description              | access url | parent              |
      | First movement | First alphorn moveement. | text.pdf   | Alphorn sheet music |
    And news content:
      | title                                | body                            | collection         | solution   | state     |
      | Purple firecraker powder price raise | Summer festivals are the cause. | Hungry Firecracker |            | validated |
      | Alphorn first movement released      | Check the sheet music.          |                    | Rich Sound | validated |
    And custom_page content:
      | title            | body                  | collection         | state     |
      | Firecrakers list | Check the list first. | Hungry Firecracker | validated |

    When I am on the homepage
    And I click "Contact Joinup Support" in the Footer region
    Then no menu items should be active in the "Header menu" menu

    # Collections menu item should be active when visiting a collection homepage.
    When I click "Collections" in the "Header menu" region
    Then "Collections" should be the active item in the "Header menu" menu
    When I click "Hungry Firecracker"
    Then "Collections" should be the active item in the "Header menu" menu
    # Collections menu item stays active inside collection content.
    When I click "Firecrakers list"
    Then "Collections" should be the active item in the "Header menu" menu
    # Go back to the homepage of the collection to check another content.
    When I click "Hungry Firecracker" in the "Header" region
    And I click "Purple firecraker powder price raise"
    Then "Collections" should be the active item in the "Header menu" menu

    # Go back again to the collection homepage and click the solution tile.
    When I click "Hungry Firecracker" in the "Header" region
    And I click "Rich Sound"
    Then "Solutions" should be the active item in the "Header menu" menu
    # Solutions menu item should stay active inside solution content.
    When I click "Alphorn first movement released"
    Then "Solutions" should be the active item in the "Header menu" menu
    # Go back to the solution homepage.
    When I click "Rich Sound" in the "Header" region
    And I click "Alphorn sheet music"
    Then "Solutions" should be the active item in the "Header menu" menu
    When I click "First movement"
    Then "Solutions" should be the active item in the "Header menu" menu

  @api @contentBlock
  Scenario: Test edit of 'Legal notice' page access.

    Given I am an anonymous user
    When I visit '/joinup/legal-notice'
    Then I should see the heading "Legal notice"
    But I should not see the contextual link "Edit" in the Content region

    Given I am logged in as an "authenticated user"
    When I visit '/joinup/legal-notice'
    Then I should see the heading "Legal notice"
    But I should not see the contextual link "Edit" in the Content region

    Given I am logged in as a moderator
    When I visit '/joinup/legal-notice'
    Then I should see the heading "Legal notice"
    And I click the contextual link "Edit" in the Content region
    Then I should see the heading "Edit custom block Legal notice"
