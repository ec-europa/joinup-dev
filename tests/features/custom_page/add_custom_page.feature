@api @group-f
Feature: "Add custom page" visibility options.
  In order to manage custom pages
  As a group facilitator
  I need to be able to add "Custom page" content through UI.

  Background:
    Given collections:
      | title           | logo     | state     |
      | Open Collective | logo.png | validated |
      | Code Camp       | logo.png | validated |
    And solutions:
      | title     | logo     | state     |
      | Parachute | logo.png | validated |
      | Skydiving | logo.png | validated |

  Scenario Outline: Links and help text for adding custom pages should should only be shown to privileged users
    # Custom pages cannot be added by normal members. Custom pages are
    # considered to be important, and are not considered 'community content'.
    # Note: Solutions don't have simple members so this check will never really occur for solutions.
    When I am logged in as a member of the "<title>" <group>
    And I go to the homepage of the "<title>" <group>
    Then I should not see the link "Add custom page"
    And I should not see the link "Add a new page" in the "Left sidebar"
    And I should not see the text "There are no pages yet. Why don't you start by creating an About page?"
    # If the normal member is promoted to facilitator, the links and help text
    # should become visible.
    Given my role in the "<title>" "<group>" changes to facilitator
    And I reload the page
    Then I should see the link "Add custom page" in the "Plus button menu"
    And I should see the contextual link "Add new page" in the "Left sidebar" region

    # An authenticated user which is not a member should also not see the links
    # and help text.
    When I am logged in as an "authenticated user"
    And I go to the homepage of the "<title>" <group>
    Then I should not see the plus button menu
    And I should not see the link "Add a new page" in the "Left sidebar"
    And I should not see the text "There are no pages yet. Why don't you start by creating an About page?"

    # An anonymous user should also not see the links and help text.
    When I am an anonymous user
    And I go to the homepage of the "<title>" <group>
    Then I should not see the plus button menu
    And I should not see the link "Add a new page" in the "Left sidebar"
    And I should not see the text "There are no pages yet. Why don't you start by creating an About page?"

    # A facilitator should see it.
    When I am logged in as a facilitator of the "<title>" <group>
    And I go to the homepage of the "<title>" <group>
    Then I should see the link "Add custom page" in the "Plus button menu"
    And I should see the contextual link "Add new page" in the "Left sidebar" region

    Examples:
      | title     | group      |
      | Code Camp | collection |
      | Parachute | solution   |

  @uploadFiles:test.zip
  Scenario Outline: Add custom page as a facilitator.
    And I am logged in as a facilitator of the "<second title>" <group>

    # Initially there are no custom pages. A help text should inform the user
    # that it is possible to add custom pages.
    When I go to the homepage of the "<second title>" <group>
    Then the "<second title>" <group> should have 0 custom pages
    And I should see the contextual link "Add new page" in the "Left sidebar" region
    When I click the contextual link "Add new page" in the "Left sidebar" region
    Then I should see the heading "Add custom page"
    And the following fields should be present "Title, Body, Published"

    # The sections about managing revisions and groups should not be visible.
    And I should not see the text "Revision information"
    And the following fields should not be present "Groups audience, Other groups, Create new revision, Revision log message"

    When I fill in the following:
      | Title | About us |
    And I enter "We are open about everything!" in the "Body" wysiwyg editor
    And I attach the file "test.zip" to "Add a new file"
    And I press "Upload"
    # The "Description" field is the description of the file.
    And I fill in "Description" with "Test file"
    And I uncheck "Published"
    And I press "Save"
    Then I should see the heading "About us"
    And I should see the success message "Custom page About us has been created."
    And I should see the text "Attachments"
    # The description of the file is set as the text to display.
    And I should see the link "Test file"
    And the "<second title>" <group> should have a custom page titled "About us"
    # Check that the link to the custom page is visible on the group page.
    When I go to the homepage of the "<second title>" <group>
    And I click "About us" in the "Left sidebar"

    # I should not be able to add a custom page to a different <group>
    When I go to the homepage of the "<first title>" <group>
    Then I should not see the link "Add custom page"

    When I am an anonymous user
    And I go to the homepage of the "<second title>" <group>
    Then I should not see the link "About us" in the "Left sidebar"

    Examples:
      | first title | second title    | group      |
      | Code Camp   | Open Collective | collection |
      | Parachute   | Skydiving       | solution   |

  Scenario: Moderators can access the "Add custom page" menu item.
    Given users:
      | Username | Roles     |
      | Falstad  | moderator |

    Given I am logged in as "Falstad"
    When I go to the homepage of the "Open Collective" collection
    Then I should see the link "Add custom page" in the "Plus button menu"
    When I go to the homepage of the "Code Camp" collection
    Then I should see the link "Add custom page" in the "Plus button menu"
    When I go to the homepage of the "Parachute" solution
    Then I should see the link "Add custom page" in the "Plus button menu"
    When I go to the homepage of the "Skydiving" solution
    Then I should see the link "Add custom page" in the "Plus button menu"

  @javascript
  Scenario: Long list of attachments should be collapsed.
    Given the following collection:
      | title | Aggressive Rubber |
      | state | validated         |
    # Create custom pages with 5 and 6 attachments.
    # 5 is the limit before adding the "Show more" functionality.
    And custom_page content:
      | title          | body                 | collection        | attachments                                                          |
      | Rubber bands   | The aggressive ones. | Aggressive Rubber | empty.rdf, empty_pdf.pdf, invalid_adms.rdf, test.zip, text.pdf       |
      | Elastic rubber | Also aggressive.     | Aggressive Rubber | ada.png, alan.jpg, blaise.jpg, charles.jpg, leonardo.jpg, linus.jpeg |

    When I go to the "Rubber bands" custom page
    Then the "empty.rdf" link in the Content region should be visible
    And the "empty_pdf.pdf" link in the Content region should be visible
    And the "invalid_adms.rdf" link in the Content region should be visible
    And the "test.zip" link in the Content region should be visible
    And the "text.pdf" link in the Content region should be visible
    But I should not see the link "Show more" in the Content region

    When I go to the "Elastic rubber" custom page
    Then the "ada.png" link in the Content region should be visible
    And the "alan.jpg" link in the Content region should be visible
    And the "blaise.jpg" link in the Content region should be visible
    And the "charles.jpg" link in the Content region should be visible
    And the "leonardo.jpg" link in the Content region should be visible
    And the "Show more" link in the Content region should be visible
    # The sixth element should be hidden.
    But the "linus.jpeg" link in the Content region should not be visible

    # Expand the list. The sixth element should become visible.
    When I click "Show more"
    Then the "linus.jpeg" link in the Content region should be visible
    And the "Show less" link in the Content region should be visible
    But I should not see the link "Show more" in the Content region

    # Collapse again the list.
    When I click "Show less"
    And I wait for animations to finish
    Then the "linus.jpeg" link in the Content region should not be visible
    And the "Show more" link in the Content region should be visible
    But I should not see the link "Show less" in the Content region

  Scenario Outline: Creating a custom page with a title that already exists.
    And custom_page content:
      | title        | body        | <group>       |
      | Rubber boots | For outside | <first title> |

    # It should not be possible to create a custom page with a duplicate title.
    Given I am logged in as a facilitator of the "<first title>" <group>
    When I go to the homepage of the "<first title>" <group>
    And I click the contextual link "Add new page" in the "Left sidebar" region
    Then I should see the heading "Add custom page"

    When I fill in the following:
      | Title | Rubber boots |
    And I enter "We love mud!" in the "Body" wysiwyg editor
    And I press "Save"
    Then I should see the error message "The Custom page title value (Rubber boots) is already taken by Rubber boots."

    # It should still be possible to create a custom page with the existing
    # title in a different group.
    Given I am logged in as a facilitator of the "<second title>" <group>
    When I go to the homepage of the "<second title>" <group>
    And I click the contextual link "Add new page" in the "Left sidebar" region
    Then I should see the heading "Add custom page"

    When I fill in the following:
      | Title | Rubber boots |
    And I enter "We love mud!" in the "Body" wysiwyg editor
    And I press "Save"
    Then I should see the heading "Rubber boots"
    And I should see the success message "Custom page Rubber boots has been created."

    Examples:
      | first title | second title    | group      |
      | Code Camp   | Open Collective | collection |
      | Parachute   | Skydiving       | solution   |
