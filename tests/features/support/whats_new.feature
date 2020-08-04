@api
Feature:
  As a moderator of the website
  In order to draw the attention of the users for the changes in Joinup
  I need to be able to mark the new menu item with special styling.

  Background:
    Given the following collections:
      | title               | state     |
      | Flagging collection | validated |
    And news content:
      | title      | state     | collection          |
      | Some title | validated | Flagging collection |

  Scenario: Only content entity internal URLs are allowed for flagging menu items.
    When I am logged in as a moderator
    Then I should not see the bell icon in the support menu

    When I go to "/admin/structure/menu/manage/support"
    And I click "Add link"
    And I fill in "Menu link title" with "Check what's new"
    And I check "Live link"

    # Absolute URLs are not allowed.
    And I fill in "Link" with "http://example.com"
    And I press "Save"
    Then I should see the error message "Flagging is allowed only for internal URLs pointing to content entities in the website."

    # RDF entities are not allowed either.
    When I fill in "Link" with "/collection/flagging-collection"
    And I press "Save"
    Then I should see the error message "Flagging is allowed only for internal URLs pointing to content entities in the website."

    # Content entities are allowed.
    When I fill in "Link" with "/collection/flagging-collection/news/some-title"
    And I press "Save"
    Then I should see the success message "The menu link has been saved."
    And I should see the bell icon in the support menu
    And the "Check what's new" link should be featured as what's new

    Given I am an anonymous user
    And I am on the homepage
    Then I should not see the bell icon in the support menu
    And the "Check what's new" link should not be featured as what's new

    Given I am logged in as a user with the authenticated role
    And I am on the homepage
    And I should see the bell icon in the support menu
    When I click "Check what's new"
    Then I should see the heading "Some title"
    And I should not see the bell icon in the support menu
    And the "Check what's new" link should not be featured as what's new

    Given I am an anonymous user
    And I am on the homepage
    Then I should not see the bell icon in the support menu
    And the "Check what's new" link should not be featured as what's new

  @javascript
  Scenario: Manual selecting of the content also works with flagging.
    When I am logged in as a moderator
    And I go to "/admin/structure/menu/manage/support"

    When I click "Add link"
    And I fill in "Menu link title" with "What is new you say?"
    And I check "Live link"
    When I type "Some t" in the "Link" autocomplete field
    Then I wait until the page contains the text "Some title"
    And I pick "Some title" from the "Link" autocomplete suggestions
    And I press "Save"
    Then I should see the success message "The menu link has been saved."
    And I should see the bell icon in the support menu

    Given I am an anonymous user
    And I am on the homepage
    Then I should not see the bell icon in the support menu
