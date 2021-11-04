@api @clearStaticCache @group-a
Feature: Asset distribution overview on solution.
  In order to view an overview of a solution's releases and download them
  As a user of the website
  I need to be able to view the releases of a solution.

  Scenario: Releases should be available in the overview page.
    Given the following collection:
      | title | End of Past |
      | state | validated   |
    And the following solutions:
      | title            | collection  | description        | state     |
      | Lovely Butterfly | End of Past | Sample description | validated |

    # A solution with no releases or standalone distributions has no button.
    When I go to the homepage of the "Lovely Butterfly" solution
    Then I should not see the link "Download releases"
    When I go to "/collection/end-of-past/solution/lovely-butterfly/releases"
    Then the response status code should be 404

    # The release numbers do not follow the creation date to ensure proper
    # ordering. "The Child of the Past" should be shown first as it is the
    # latest release created, even though it is not the latest in the version
    # number.
    And the following releases:
      | title                 | documentation | release number | release notes | creation date    | is version of    | state     |
      | Hidden spies          | text.pdf      | 3              | Notes 3       | 28-01-1995 12:05 | Lovely Butterfly | draft     |
      | Thief in the Angels   | text.pdf      | 2              | Notes 2       | 28-01-1995 12:06 | Lovely Butterfly | validated |
      | The Child of the Past | text.pdf      | 1              | Notes 1       | 28-01-1996 12:05 | Lovely Butterfly | validated |
    And the following asset distributions:
      | title       | access url                          | creation date    | parent                |
      | Linux       | test.zip                            | 28-01-1995 12:05 | Thief in the Angels   |
      | Windows     | http://www.example.org/download.php | 28-01-1995 12:06 | The Child of the Past |
      | User manual | test.zip                            | 28-01-1995 11:07 | Lovely Butterfly      |
      | Solaris     | test.zip                            | 28-01-1995 12:08 | Hidden spies          |

    When I go to the homepage of the "Lovely Butterfly" solution
    And I click "Download releases"
    Then I should see the heading "Releases for Lovely Butterfly solution"
    And the page should be cacheable

    # Check that clean URLs are being applied to the "releases" subpage.
    And I should be on "/collection/end-past/solution/lovely-butterfly/releases"

    And I should see the heading "Lovely Butterfly" in the Header region
    # The general link "Download releases" should not be shown on the releases
    # page itself, since it references the page we are already on.
    But I should not see the link "Download releases"

    # Only the published releases should be shown.
    # The release titles include the version as a suffix.
    And I should see the following releases in the exact order:
      | release                 |
      | The Child of the Past 1 |
      | Thief in the Angels 2   |
    # The standalone distribution should also be visible.
    And I should see the link "User manual"
    And I should see the text "Standalone distribution"
    # Distributions of unpublished releases should not be shown.
    But I should not see the text "Solaris"

    And I should see the download link in the "Linux" asset distribution
    And I should see the download link in the "User manual" asset distribution
    And I should see the external link in the "Windows" asset distribution

    And the "The Child of the Past" release should be marked as the latest release

    # Verify that the releases titles link to the release page.
    And I should see the link "The Child of the Past 1"
    And I should see the link "Thief in the Angels 2"
    When I click "The Child of the Past 1"
    Then I should see the heading "The Child of the Past 1"
    And I should see the text "Latest release"
    And the page should be cacheable

    # Create a new release.
    When I am logged in as a facilitator of the "Lovely Butterfly" solution
    And I go to the homepage of the "Lovely Butterfly" solution
    And I click "Add release" in the plus button menu
    Then I should see the heading "Add Release"
    When I fill in "Name" with "The Deep Doors"
    And I fill in "Release number" with "4"
    And I enter "Notes 4" in the "Release notes" wysiwyg editor

    # Regression test. First save as draft and then publish to ensure
    # proper cache invalidation.
    # @see: ISAICP-3128
    And I press "Save as draft"
    Then I should see the heading "The Deep Doors 4"
    When I am an anonymous user
    And I go to the homepage of the "Lovely Butterfly" solution
    And I click "Download releases"
    Then I should see the following releases in the exact order:
      | release                 |
      | The Child of the Past 1 |
      | Thief in the Angels 2   |
    And the "The Child of the Past" release should be marked as the latest release
    When I click "Thief in the Angels 2"
    Then I should see the heading "Thief in the Angels 2"
    When I click "Switch to the latest release"
    Then I should see the heading "The Child of the Past 1"
    And I should see the text "Latest release"

    # Publish the release as a solution facilitator.
    When I am logged in as a facilitator of the "Lovely Butterfly" solution
    And I go to the homepage of the "The Deep Doors" release
    When I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then I should see the heading "The Deep Doors 4"

    # We need to logout as unpublished releases are also shown in the list for
    # privileged users.
    # @see ISAICP-3393
    When I am an anonymous user
    And I go to the homepage of the "Lovely Butterfly" solution
    And I click "Download releases"
    Then I should see the following releases in the exact order:
      | release                 |
      | The Deep Doors 4        |
      | The Child of the Past 1 |
      | Thief in the Angels 2   |
    And the "The Deep Doors" release should be marked as the latest release

    # Check that the cache is invalidated.
    When I delete the "Linux" asset distribution
    And I delete the "Windows" asset distribution
    And I delete the "User manual" asset distribution
    And I delete the "Solaris" asset distribution
    And I delete the "Hidden spies" release
    And I delete the "Thief in the Angels" release
    And I delete the "The Child of the Past" release
    And I delete the "The Deep Doors" release
    When I go to the homepage of the "Lovely Butterfly" solution
    Then I should not see the link "Download releases"
    When I go to "/collection/end-past/solution/lovely-butterfly/releases"
    Then the response status code should be 404
