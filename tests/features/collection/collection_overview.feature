@api @group-a
Feature: Communities Overview

  Scenario: Check visibility of "Communities" menu link.
    Given I am an anonymous user
    When I am on the homepage
    Then I should see the link "Communities"
    When I click "Communities"
    Then I should see the heading "Communities"
    And I should see the text "Communities are the main collaborative space where the content items are organised around a common topic or domain and where the users can share their content and engage their community."
    # Check that all logged in users can see and access the link as well.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Communities"
    When I click "Communities"
    Then I should see the heading "Communities"

  @terms @uploadFiles:logo.png,banner.jpg
  Scenario: View community overview as an anonymous user
    Given users:
      | Username      | E-mail                       |
      | Madam Shirley | i.see.the.future@example.com |
    And communities:
    # As of ISAICP-3618 descriptions should not be visible in regular tiles.
      | title             | description                    | creation date     | state     |
      | E-health          | Supports health-related fields | 2018-10-04 8:31am | validated |
      | Open Data         | Facilitate access to data sets | 2018-10-04 8:33am | validated |
      | Connecting Europe | Reusable tools and services    | 2018-10-04 8:32am | validated |
    And the following owner:
      | name                 | type                    |
      | Organisation example | Non-Profit Organisation |
    # Check that visiting as an anonymous does not create cache for all users.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Communities"
    And I click "Communities"
    And I should see the text "Communities are the main collaborative space"
    And the page should be cacheable

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
    And I am on the homepage
    And I click "Communities"
    Then I should see the following tiles in the correct order:
      # Created in 8:33am.
      | Open Data         |
      # Created in 8:32am.
      | Connecting Europe |
      # Created in 8:31am.
      | E-health          |

    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Communities"
    When I click "Communities"
    Then I should see the link "E-health"
    And I should not see the text "Supports health-related fields"
    And I should see the link "Open Data"
    And I should not see the text "Facilitate access to data sets"
    And I should see the link "Connecting Europe"
    And I should not see the text "Reusable tools and services"
    And the page should be cacheable

    When I click "E-health"
    Then I should see the heading "E-health"

    # Add new community as a moderator to directly publish it.
    Given I am logged in as a moderator
    When I go to the propose community form
    Then I should see the heading "Propose community"
    When I fill in the following:
      | Title       | Colonies in space                   |
      | Description | Some space mumbo jumbo description. |
      # Contact information data.
      | Name        | Overviewer contact                  |
      | E-mail      | overviewer@example.com              |
    And I select "Employment and Support Allowance" from "Topic"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    And I press "Publish"
    Then I visit the "Colonies in space" community
    Then I should see the text "Colonies in space"

    And I am on the homepage
    And I click "Communities"
    Then I should see the text "Colonies in space"
    And the page should be cacheable

    # @todo: Normally the community should go through a moderation process.
    # It will not be immediately available.
    # Check the new community as an anonymous user.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Communities"
    When I click "Communities"
    Then I should see the link "Colonies in space"

    # Clean up the community that was created manually.
    Then I delete the "Colonies in space" community
    And I delete the "Overviewer contact" contact information

  @terms
  Scenario: Custom pages should not be visible on the overview page
    Given the following community:
      | title            | Jira       |
      | logo             | logo.png   |
      | moderation       | no         |
      | topic            | Demography |
      | spatial coverage | Belgium    |
      | state            | validated  |
    And news content:
      | title                             | body                             | community | topic                   | spatial coverage | state     |
      | Jira will be down for maintenance | As always, during business hours | Jira       | Statistics and Analysis | Luxembourg       | validated |
    And custom_page content:
      | title            | body                                       | community |
      | Maintenance page | Jira is re-indexing. Go and drink a coffee | Jira       |
    When I go to the homepage of the "Jira" community
    Then I should see the "Jira will be down for maintenance" tile
    And I should not see the "Maintenance page" tile

  Scenario: Community overview active trail should persist on urls with arguments.
    Given I am an anonymous user
    And I visit "/communities?a=1"
    Then "Communities" should be the active item in the "Header menu" menu

  Scenario: Users are able to filter communities they have created or that are featured site-wide.
    Given users:
      | Username          |
      | Carolina Mercedes |
      | Luigi Plant       |
      | Yiannis Parios    |
    And the following communities:
      | title                       | state     | featured | author            |
      | Enemies of the state        | validated | yes      | Luigi Plant       |
      | Fed up meatlovers           | validated | no       | Carolina Mercedes |
      | Ugly farmers                | validated | yes      | Luigi Plant       |
      | Yiannis Parios community 1 | validated | no       | Yiannis Parios    |
      | Yiannis Parios community 2 | validated | no       | Yiannis Parios    |
      | Yiannis Parios community 3 | validated | no       | Yiannis Parios    |
    # Technical: use a separate step to create a community associated to the anonymous user.
    And the following community:
      | title    | Biologic meatballs |
      | state    | validated          |
      | featured | no                 |

    When I am logged in as "Yiannis Parios"
    And I click "Communities"
    Then the "My communities content" inline facet should allow selecting the following values:
      | My communities (3)       |
      | Featured communities (2) |
    And the page should be cacheable

    When I click "My communities" in the "My communities content" inline facet
    Then I should see the following tiles in the correct order:
      | Yiannis Parios community 1 |
      | Yiannis Parios community 2 |
      | Yiannis Parios community 3 |
    And the "My communities content" inline facet should allow selecting the following values:
      | Featured communities (2) |
      | All communities          |
    And the page should be cacheable

    # Regression test to ensure that the facets are cached by user.
    # Subsequent page loads of the communities page would lead to cached facets
    # to be leaked to other users.
    # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3777
    When I click "All communities" in the "My communities content" inline facet
    Then the "My communities content" inline facet should allow selecting the following values:
      | My communities (3)       |
      | Featured communities (2) |
    And the page should be cacheable

    When I am logged in as "Carolina Mercedes"
    When I click "Communities"
    Then the "My communities content" inline facet should allow selecting the following values:
      | Featured communities (2) |
      | My communities (1)       |
    And the page should be cacheable

    When I click "My communities" in the "My communities content" inline facet
    Then I should see the following tiles in the correct order:
      | Fed up meatlovers |
    And the "My communities content" inline facet should allow selecting the following values:
      | Featured communities (2) |
      | All communities          |
    And the page should be cacheable
    # Verify that the facets are cached for the correct user by visiting again
    # the communities page without any facet filter.
    When I click "All communities" in the "My communities content" inline facet
    Then the "My communities content" inline facet should allow selecting the following values:
      | Featured communities (2) |
      | My communities (1)       |
    And the page should be cacheable

    When I am an anonymous user
    And I click "Communities"
    # The anonymous user has no access to the "My communities" facet entry.
    Then the "My communities content" inline facet should allow selecting the following values:
      | Featured communities (2) |
    And the page should be cacheable

    When I click "Featured communities" in the "My communities content" inline facet
    Then I should see the following tiles in the correct order:
      | Enemies of the state |
      | Ugly farmers         |
    And the "My communities content" inline facet should allow selecting the following values:
      | All communities |
    And the page should be cacheable

    When I click "All communities" in the "My communities content" inline facet
    Then the "My communities content" inline facet should allow selecting the following values:
      | Featured communities (2) |
    And the page should be cacheable

    When I am logged in as "Carolina Mercedes"
    And I click "Communities"
    And I click "Featured communities" in the "My communities content" inline facet
    Then I should see the following tiles in the correct order:
      | Enemies of the state |
      | Ugly farmers         |
    And the page should be cacheable
