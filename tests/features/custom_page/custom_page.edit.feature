@api
Feature: "Custom page" editing.
  In order to manage custom pages
  As a collection member
  I need to be able to edit "Custom Page" content through UI.
  Background:
    Given users:
      | name            | mail                            | pass |
      | Vaggelis Edit   | custom_page_edit@example.com    | test |
    And the following collections:
      | uri                                   | name           | description               | logo     |
      | http://joinup.eu/custom_page/edit/foo | Foo Collection | This is a foo collection. | logo.png |
      | http://joinup.eu/custom_page/edit/bar | Bar Collection | This is a bar connection. | logo.png |
    And the following user memberships:
      | group_type | group_id                               | member          |
      | rdf_entity | http://joinup.eu/custom_page/edit/foo  | Vaggelis Edit   |
    And custom_page content:
      | title      | body                                     | groups audience                       |
      | Dummy Page | This is some dummy content like foo:bar. | http://joinup.eu/custom_page/edit/foo |

  Scenario: Check visibility of edit button.
    When I am logged in as "Vaggelis Edit"
    And I am viewing my "Custom Page" content with the title "Dummy Page"
    Then I should see the link "Edit"
    When I am logged in as a user with the authenticated role
    And I am viewing my "Custom Page" content with the title "Dummy Page"
    Then I should not see the link "Edit"
  Scenario: Edit custom page as a collection member.
    When I am logged in as "Vaggelis Edit"
    And I am viewing my "Custom Page" content with the title "Dummy Page"
    And I click "Edit"
    Then I should see the heading "Edit Dummy Page"
    And the following fields should be present "Title, Body"
    And the following fields should not be present "Groups audience, Other groups"
    When I fill in the following:
      | Title       | Edited Dummy Page                                                       |
      | Body        | This is some dummy description not meant to explain or define anything. |
    And I press "Save"
    Then I should have a "Custom page" content page titled "Edited Dummy Page"
    And I should see the text "This is some dummy description not meant to explain or define anything."
    And the "Custom page title" of type "node" should be a member of the group with ID "http://joinup.eu/collection/foo"
