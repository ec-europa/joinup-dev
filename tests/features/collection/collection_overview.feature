@api
Feature: Collections Overview

  Scenario: Check visibility of "Collections" menu link.
    Given I am an anonymous user
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"
    And I should see the text "Collections are the main collaborative space where the content items are organised around a common topic or domain and where the users can share their content and engage their community."
    # Check that all logged in users can see and access the link as well.
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the heading "Collections"

  # @todo The small header, which contains collections link, should be removed for anonymous users on the homepage - https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2639.
  @terms
  Scenario: View collection overview as an anonymous user
    Given users:
      | Username      | E-mail                       |
      | Madam Shirley | i.see.the.future@example.com |
    Given collections:
    # As of ISAICP-3618 descriptions should not be visible in regular tiles.
      | title             | description                    | creation date     | state     |
      | E-health          | Supports health-related fields | 2018-10-04 8:31am | validated |
      | Open Data         | Facilitate access to data sets | 2018-10-04 8:33am | validated |
      | Connecting Europe | Reusable tools and services    | 2018-10-04 8:32am | validated |
    Given the following owner:
      | name                 | type                    |
      | Organisation example | Non-Profit Organisation |
    # Check that visiting as an anonymous does not create cache for all users.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Collections"
    And I click "Collections"

    # Check page for authenticated users.
    When I am logged in as "Madam Shirley"
    And I am on the homepage
    And I click "Collections"
    Then I should see the following tiles in the correct order:
      # Created in 8:33am.
      | Open Data         |
      # Created in 8:32am.
      | Connecting Europe |
      # Created in 8:31am.
      | E-health          |

    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the link "E-health"
    And I should not see the text "Supports health-related fields"
    And I should see the link "Open Data"
    And I should not see the text "Facilitate access to data sets"
    And I should see the link "Connecting Europe"
    And I should not see the text "Reusable tools and services"
    When I click "E-health"
    Then I should see the heading "E-health"

    # Add new collection as a moderator to directly publish it.
    Given I am logged in as a moderator
    When I go to the propose collection form
    Then I should see the heading "Propose collection"
    When I fill in the following:
      | Title       | Colonies in space                   |
      | Description | Some space mumbo jumbo description. |
    When I select "Employment and Support Allowance" from "Policy domain"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    And I press "Publish"
    Then I visit the "Colonies in space" collection
    Then I should see the text "Colonies in space"

    And I am on the homepage
    And I click "Collections"
    Then I should see the text "Colonies in space"

    # @todo: Normally the collection should go through a moderation process.
    # It will not be immediately available.
    # Check the new collection as an anonymous user.
    When I am an anonymous user
    And I am on the homepage
    Then I should see the link "Collections"
    When I click "Collections"
    Then I should see the link "Colonies in space"

    # Clean up the collection that was created manually.
    Then I delete the "Colonies in space" collection

  @terms
  Scenario: View collection detailed information in the About page
    Given the following owner:
      | name         | type                |
      | Tamsin Irwin | Industry consortium |
    And the following contact:
      | email       | irwinbvba@example.com        |
      | name        | Irwin BVBA made-up company   |
      | Website URL | http://www.example.org/irwin |
    And the following collection:
      | title               | Fitness at work                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
      | description         | <p>This collection is intended to show ways of being <strong>fit while working</strong>.</p><p>Integer diam purus molestie in est sit amet tincidunt gravida dolor. Vivamus dui nisi semper et tellus a lobortis tristique felis. Praesent sagittis orci id sodales finibus. Morbi purus urna imperdiet vitae est a porta semper dui. Curabitur scelerisque non mi at facilisis. Nullam blandit euismod ipsum vel varius arcu fermentum nec. In ligula sapien tempor non venenatis ac tincidunt sed nunc. In consequat sapien risus a malesuada eros auctor eget. Curabitur at ultricies mi at varius nunc. Orci varius natoque penatibus et magnis dis parturient montes nascetur ridiculus mus. Curabitur egestas massa nec semper sagittis orci urna semper nulla at dictum ligula ipsum sit amet urna. Fusce euismod luctus ullamcorper. In quis porttitor arcu.</p> |
      | policy domain       | E-health                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
      | owner               | Tamsin Irwin                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
      | abstract            | <strong>Fit while working</strong> is dope. Lorem ipsum dolor sit amet consectetur adipiscing elit. Suspendisse diam nunc blandit vitae faucibus nec laoreet sit amet lectus. Cras faucibus augue velit et aliquet sem dictum vel. Aenean rutrum iaculis imperdiet. Proin faucibus varius turpis a fringilla ante sodales non. Donec vel purus metus. Fusce pellentesque eros dolor. Donec tempor ipsum id erat ullamcorper pulvinar. Pellentesque eget dolor nunc. Vivamus libero leo blandit a ornare non sollicitudin iaculis purus. Integer nec enim facilisis mi fermentum mollis sed vitae lacus.                                                                                                                                                                                                                                                                  |
      | contact information | Irwin BVBA made-up company                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
      | policy domain       | Statistics and Analysis                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
      | spatial coverage    | Belgium                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
      | closed              | no                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
      | elibrary creation   | members                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
      | moderation          | no                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
      | state               | validated                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |

    When I go to the homepage of the "Fitness at work" collection
    # Check for HTML so that we assert that actually the HTML has been stripped.
    Then the page should contain the html text "Fit while working is dope"
    And I should see the text "leo blandit a ornare non sollicitudin iaculis…"
    # Check that later chunks of text in the abstract are not rendered.
    But I should not see the text "purus. Integer nec enim facilisis mi fermentum mollis sed vitae lacus"
    And I should not see the text "This collection is intended to show ways of being fit while working"

    # The 'Read more' link leads to the About page.
    When I click "Read more" in the "Content" region
    Then I should see the heading "About Fitness at work"

    And I should see the text "Fit while working is dope"
    And I should see the text "This collection is intended to show ways of being fit while working"
    And I should see the text "Tamsin Irwin"
    And I should see the text "Irwin BVBA made-up company"
    # The following 2 fields should not be visible after change request in ISAICP-3664.
    And I should not see the text "E-health"
    And I should not see the text "Belgium"

    # When there is no abstract, the description should be shown in the homepage.
    When I am logged in as a "moderator"
    And I go to the "Fitness at work" collection edit form
    And I fill in "Abstract" with ""
    And I press "Publish"
    Then I should see the heading "Fitness at work"
    And the page should contain the html text "This collection is intended to show ways of being fit while working"
    And I should see the text "In consequat sapien risus a…"
    But I should not see the text "malesuada eros auctor eget. Curabitur at"
    When I click "Read more" in the "Content" region
    Then I should see the heading "About Fitness at work"

  @terms
  Scenario: Custom pages should not be visible on the overview page
    Given the following collection:
      | title            | Jira       |
      | logo             | logo.png   |
      | moderation       | no         |
      | policy domain    | Demography |
      | spatial coverage | Belgium    |
      | state            | validated  |
    And news content:
      | title                             | body                             | collection | policy domain           | spatial coverage | state     |
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
    Then the "My collections content" inline facet should allow selecting the following values "My collections (3), Featured collections (2)"
    When I click "My collections" in the "My collections content" inline facet
    Then I should see the following tiles in the correct order:
      | Yiannis Parios collection 1 |
      | Yiannis Parios collection 2 |
      | Yiannis Parios collection 3 |
    And the "My collections content" inline facet should allow selecting the following values "Featured collections (2), All collections"
    # Regression test to ensure that the facets are cached by user.
    # Subsequent page loads of the collections page would lead to cached facets
    # to be leaked to other users.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3777
    When I click "All collections" in the "My collections content" inline facet
    Then the "My collections content" inline facet should allow selecting the following values "My collections (3), Featured collections (2)"

    When I am logged in as "Carolina Mercedes"
    When I click "Collections"
    Then the "My collections content" inline facet should allow selecting the following values "Featured collections (2), My collections (1)"
    When I click "My collections" in the "My collections content" inline facet
    Then I should see the following tiles in the correct order:
      | Fed up meatlovers |
    And the "My collections content" inline facet should allow selecting the following values "Featured collections (2), All collections"
    # Verify that the facets are cached for the correct user by visiting again
    # the collections page without any facet filter.
    When I click "All collections" in the "My collections content" inline facet
    Then the "My collections content" inline facet should allow selecting the following values "Featured collections (2), My collections (1)"

    When I am an anonymous user
    And I click "Collections"
    # The anonymous user has no access to the "My collections" facet entry.
    Then the "My collections content" inline facet should allow selecting the following values "Featured collections (2)"
    When I click "Featured collections" in the "My collections content" inline facet
    Then I should see the following tiles in the correct order:
      | Enemies of the state |
      | Ugly farmers         |
    And the "My collections content" inline facet should allow selecting the following values "All collections"
    When I click "All collections" in the "My collections content" inline facet
    Then the "My collections content" inline facet should allow selecting the following values "Featured collections (2)"

    When I am logged in as "Carolina Mercedes"
    And I click "Collections"
    And I click "Featured collections" in the "My collections content" inline facet
    Then I should see the following tiles in the correct order:
      | Enemies of the state |
      | Ugly farmers         |
