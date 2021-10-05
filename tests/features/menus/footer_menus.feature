@api
Feature:
  As a moderator of the website
  In order to present proper footer information to the user
  I need to be able to manage the footer menus.

  Scenario: A moderator can see the footer menus in the 'Menus' item of the admin toolbar.
    Given I am logged in as a moderator
    And I am on the homepage
    Then I should not see the link "Some random link" in the "Footer"
    Then I should see the link "Menus" in the "Administration toolbar"

    When I click "Menus" in the "Administration toolbar"
    Then I should see the heading "Menus"
    And I should see the following lines of text:
      | About us            |
      | European Commission |
      | Follow us           |
      | Help and support    |
      | Social media        |
      | Support             |

    When I click "Add link" in the "About us" row
    And I fill in "Menu link title" with "Some random link"
    And I fill in "Link" with "/collections"
    And I press "Save"
    Then I should see the success message "The menu link has been saved."

    When I am on the homepage
    Then I should see the link "Some random link" in the "Footer"

    When I click "Menus" in the "Administration toolbar"
    And I click "List links" in the "About us" row
    And I click "Delete" in the "Some random link" row
    And I press "Delete"
    Then I should see the success message "The menu link Some random link has been deleted."

    When I am on the homepage
    Then I should not see the link "Some random link" in the "Footer"
