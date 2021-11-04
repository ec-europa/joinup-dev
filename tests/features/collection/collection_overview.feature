@api @group-d
Feature: Collections Overview

  Scenario: Check visibility of "Collections" menu link.
    Given I am an anonymous user
    # The homepage no longer has a link to the collections overview. Let's try another page.
    When I am on the search page
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"
    And I should see the text "Collections are the main collaborative space where the content items are organised around a common topic or domain and where the users can share their content and engage their community."
    # Check that all logged in users can see and access the link as well.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"

  @terms @uploadFiles:logo.png,banner.jpg
  Scenario: View collection overview as an anonymous user
    Given users:
      | Username      | E-mail                       |
      | Madam Shirley | i.see.the.future@example.com |
    And collections:
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
    And I visit the collection overview
    And I should see the text "Collections are the main collaborative space"
    And the page should be cacheable

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
    And I visit the collection overview
    Then I should see the following tiles in the correct order:
      # Created in 8:33am.
      | Open Data         |
      # Created in 8:32am.
      | Connecting Europe |
      # Created in 8:31am.
      | E-health          |

    When I am an anonymous user
    And I visit the collection overview
    Then I should see the link "E-health"
    And I should not see the text "Supports health-related fields"
    And I should see the link "Open Data"
    And I should not see the text "Facilitate access to data sets"
    And I should see the link "Connecting Europe"
    And I should not see the text "Reusable tools and services"
    And the page should be cacheable

    When I click "E-health"
    Then I should see the heading "E-health"

    # Add new collection as a moderator to directly publish it.
    Given I am logged in as a moderator
    When I go to the propose collection form
    Then I should see the heading "Propose collection"
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
    Then I visit the "Colonies in space" collection
    Then I should see the text "Colonies in space"

    When I visit the collection overview
    Then I should see the text "Colonies in space"
    And the page should be cacheable

    # @todo: Normally the collection should go through a moderation process.
    # It will not be immediately available.
    # Check the new collection as an anonymous user.
    When I am an anonymous user
    And I visit the collection overview
    Then I should see the link "Colonies in space"

    # Clean up the collection that was created manually.
    Then I delete the "Colonies in space" collection
    And I delete the "Overviewer contact" contact information

  @terms
  Scenario: Custom pages should not be visible on the overview page
    Given the following collection:
      | title            | Jira       |
      | logo             | logo.png   |
      | moderation       | no         |
      | topic            | Demography |
      | spatial coverage | Belgium    |
      | state            | validated  |
    And news content:
      | title                             | body                             | collection | topic                   | spatial coverage | state     |
      | Jira will be down for maintenance | As always, during business hours | Jira       | Statistics and Analysis | Luxembourg       | validated |
    And custom_page content:
      | title            | body                                       | collection |
      | Maintenance page | Jira is re-indexing. Go and drink a coffee | Jira       |
    When I go to the homepage of the "Jira" collection
    Then I should see the "Jira will be down for maintenance" tile
    And I should not see the "Maintenance page" tile

  Scenario: Collection overview active trail should persist on urls with arguments.
    Given I am an anonymous user
    And I visit "/collections?a=1"
    Then "Collections" should be the active item in the "Header menu" menu

  Scenario: Users are able to filter collections they have created or that are featured site-wide.
    Given users:
      | Username          |
      | Carolina Mercedes |
      | Luigi Plant       |
      | Yiannis Parios    |
    And the following collections:
      | title                       | state     | featured | author            |
      | Enemies of the state        | validated | yes      | Luigi Plant       |
      | Fed up meatlovers           | validated | no       | Carolina Mercedes |
      | Ugly farmers                | validated | yes      | Luigi Plant       |
      | Yiannis Parios collection 1 | validated | no       | Yiannis Parios    |
      | Yiannis Parios collection 2 | validated | no       | Yiannis Parios    |
      | Yiannis Parios collection 3 | validated | no       | Yiannis Parios    |
    # Technical: use a separate step to create a collection associated to the anonymous user.
    And the following collection:
      | title    | Biologic meatballs |
      | state    | validated          |
      | featured | no                 |

    When I am logged in as "Yiannis Parios"
    And I click "Collections"
    Then the "My collections content" inline facet should allow selecting the following values:
      | My collections (3)       |
      | Featured collections (2) |
    And the page should be cacheable

    When I click "My collections" in the "My collections content" inline facet
    Then I should see the following tiles in the correct order:
      | Yiannis Parios collection 1 |
      | Yiannis Parios collection 2 |
      | Yiannis Parios collection 3 |
    And the "My collections content" inline facet should allow selecting the following values:
      | Featured collections (2) |
      | All collections          |
    And the page should be cacheable

    # Regression test to ensure that the facets are cached by user.
    # Subsequent page loads of the collections page would lead to cached facets
    # to be leaked to other users.
    # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3777
    When I click "All collections" in the "My collections content" inline facet
    Then the "My collections content" inline facet should allow selecting the following values:
      | My collections (3)       |
      | Featured collections (2) |
    And the page should be cacheable

    When I am logged in as "Carolina Mercedes"
    When I click "Collections"
    Then the "My collections content" inline facet should allow selecting the following values:
      | Featured collections (2) |
      | My collections (1)       |
    And the page should be cacheable

    When I click "My collections" in the "My collections content" inline facet
    Then I should see the following tiles in the correct order:
      | Fed up meatlovers |
    And the "My collections content" inline facet should allow selecting the following values:
      | Featured collections (2) |
      | All collections          |
    And the page should be cacheable
    # Verify that the facets are cached for the correct user by visiting again
    # the collections page without any facet filter.
    When I click "All collections" in the "My collections content" inline facet
    Then the "My collections content" inline facet should allow selecting the following values:
      | Featured collections (2) |
      | My collections (1)       |
    And the page should be cacheable

    When I am an anonymous user
    And I click "Collections"
    # The anonymous user has no access to the "My collections" facet entry.
    Then the "My collections content" inline facet should allow selecting the following values:
      | Featured collections (2) |
    And the page should be cacheable

    When I click "Featured collections" in the "My collections content" inline facet
    Then I should see the following tiles in the correct order:
      | Enemies of the state |
      | Ugly farmers         |
    And the "My collections content" inline facet should allow selecting the following values:
      | All collections |
    And the page should be cacheable

    When I click "All collections" in the "My collections content" inline facet
    Then the "My collections content" inline facet should allow selecting the following values:
      | Featured collections (2) |
    And the page should be cacheable

    When I am logged in as "Carolina Mercedes"
    And I click "Collections"
    And I click "Featured collections" in the "My collections content" inline facet
    Then I should see the following tiles in the correct order:
      | Enemies of the state |
      | Ugly farmers         |
    And the page should be cacheable
