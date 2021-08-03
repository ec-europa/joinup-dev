@api @group-a
Feature: Site menus
  In order to navigate through the sections of the site
  As a user
  I want to have access to site-wide menus

  Scenario: Main menu items should be active based on the current page.
    Given community:
      | title | Hungry Firecracker |
      | state | validated          |
    And solution:
      | title      | Rich Sound         |
      | collection | Hungry Firecracker |
      | state      | validated          |
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

    # Communities menu item should be active when visiting a community homepage.
    When I click "Communities" in the "Header menu" region
    Then "Communities" should be the active item in the "Header menu" menu
    When I click "Hungry Firecracker"
    Then "Communities" should be the active item in the "Header menu" menu
    # Communities menu item stays active inside community content.
    When I click "Firecrakers list"
    Then "Communities" should be the active item in the "Header menu" menu
    # Go back to the homepage of the community to check another content.
    When I click "Hungry Firecracker" in the "Header" region
    And I click "Purple firecraker powder price raise"
    Then "Communities" should be the active item in the "Header menu" menu

    # Go back again to the community homepage and click the solution tile.
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

  Scenario: Tooltip text should be set for important menu items.
    Given community:
      | title | Some community |
      | state | validated |

    When I am on the homepage
    Then I see the "a" element with the "title" attribute set to "Communities are the main collaborative space where the content items are organised around a common topic or domain and where the users can share their content and engage their community." in the "Statistics" region
    And I see the "a" element with the "title" attribute set to "A Solution on Joinup is a framework, tool, or service either hosted directly on Joinup or federated from third-party repositories." in the "Statistics" region
    And I see the "a" element with the "title" attribute set to "KEEP UP TO DATE items, like news, events, discussions and documents can be included in both Communities and Solutions." in the "Statistics" region

    When I go to "/collections"
    Then I see the "a" element with the "title" attribute set to "Communities are the main collaborative space where the content items are organised around a common topic or domain and where the users can share their content and engage their community." in the "Header menu" region
    And I see the "a" element with the "title" attribute set to "A Solution on Joinup is a framework, tool, or service either hosted directly on Joinup or federated from third-party repositories." in the "Header menu" region
    And I see the "a" element with the "title" attribute set to "KEEP UP TO DATE items, like news, events, discussions and documents can be included in both Communities and Solutions." in the "Header menu" region

    When I am logged in as a user with the facilitator role of the "Some community" community
    And I go to the "Some community" community
    Then I see the "button" element with the "title" attribute set to "Create content" in the "Plus button menu" region
