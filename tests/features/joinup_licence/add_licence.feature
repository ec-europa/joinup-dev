@api
Feature: Add a licence through the UI
  In order to manage licences
  As a moderator or a licence manager
  I need to be able to add licences through the UI.

  Background:
    # Note that the SPDX licence uses the US spelling "Licence" while the Joinup
    # licence uses the UK spelling "Licence". This is intentional since SPDX is
    # a US based institution, and Joinup is European.
    Given SPDX licences:
      | title       | ID  | see also                                                                                         | text                                                                                               |
      | MIT License | MIT | opensource.org - https://opensource.org/licenses/MIT, mit-license.org - https://mit-license.org/ | "MIT License\n\nCopyright ©2019\n\nPermission is hereby granted, free of charge, to any person..." |

  Scenario Outline: Add licence as a Moderator or a Licence Manager.
    Given I am logged in as a "<role>"
    And I am on the homepage
    # The dashboard link is visible by clicking the user icon.
    When I click "Dashboard"
    And I click "Licences overview"
    And I click "Add licence"
    Then I should see the heading "Add Licence"
    And the "Licence legal type" field should contain the "Can, Must, Cannot, Compatible, Law, Support" option groups

    When I press "Save"
    Then I should see the following error messages:
      | error messages                    |
      | Title field is required.          |
      | Description field is required.    |
      | Type field is required.           |

    When I fill in "Title" with "MIT Licence"
    And I fill in "Description" with "The classic open source licence without copyleft."
    # Ensure that the Type field is a dropdown.
    # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3342
    And I select "Public domain" from "Type"
    And I select "MIT License" from "Corresponding SPDX licence"
    And I select "Strong Community" from "Licence legal type"
    And I additionally select "Venue fixed" from "Licence legal type"
    And I press "Save"
    Then I should have 1 licence
    And I should see the heading "MIT Licence"
    And I should see the text "Description"
    And I should see the text "The classic open source licence without copyleft."
    And I should see the text "Type"
    And I should see the text "Public domain"
    And I should see the text "Corresponding SPDX licence"
    And I should see the text "MIT License"
    And I should see the text "Licence legal type"
    And I should see the text "Strong Community"
    And I should see the text "Venue fixed"
    And I should see the text "Licence ID"
    And I should see the text "MIT"
    And I should see the text "See also"
    And I should see the link "https://mit-license.org/"
    And I should see the link "https://opensource.org/licenses/MIT"
    # Check that the licence text is displayed as paragraphs, and not as a wall
    # of text.
    And I should see a paragraph containing the text "Copyright ©2019"
    And I should see a paragraph containing the text "MIT License"
    And I should see a paragraph containing the text "Permission is hereby granted, free of charge, to any person..."

    # Clean up the licence that was created through the UI.
    Then I delete the "MIT Licence" licence

    Examples:
      | role            |
      | moderator       |
      | licence_manager |
