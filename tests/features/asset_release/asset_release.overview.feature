@api
Feature: Asset distribution overview on solution.
  In order to view an overview of a solution's releases and download them
  As a user of the website
  I need to be able to view the releases of a solution.

  Scenario: Releases should be available in the overview page.
    Given the following solutions:
      | title            | description        | state     |
      | Lovely Butterfly | Sample description | validated |
    # The release numbers do not follow the creation date to ensure proper
    # ordering. "The Child of the Past" should be shown first as it is the
    # latest release created, even though it is not the latest in the version
    # number.
    And the following releases:
      | title                 | documentation | release number | release notes | creation date    | is version of    |
      | Thief in the Angels   | text.pdf      | 2              | Notes 2       | 28-01-1995 12:06 | Lovely Butterfly |
      | The Child of the Past | text.pdf      | 1              | Notes 1       | 28-01-1996 12:05 | Lovely Butterfly |
    And the following asset distributions:
      | title       | access url | creation date    | parent                |
      | Linux       | test.zip   | 28-01-1995 12:05 | Thief in the Angels   |
      | Windows     |            | 28-01-1995 12:06 | The Child of the Past |
      | User manual | test.zip   | 28-01-1995 11:07 | Lovely Butterfly      |
    And the following collection:
      | title      | End of Past      |
      | affiliates | Lovely Butterfly |
      | state      | validated        |

    When I go to the homepage of the "Lovely Butterfly" solution
    And I click "Download"
    Then I should see the heading "Releases for Lovely Butterfly solution"
    # The release titles include the version as a suffix.
    And I should see the following releases in the exact order:
      | release                 |
      | The Child of the Past 1 |
      | Thief in the Angels 2   |
    # The standalone distribution should also be visible.
    And I should see the link "User manual"
    And I should see the text "Standalone distribution"

    And I should see the download link in the "Linux" asset distribution
    And the "Windows" asset distribution should not have any download urls

    And the "The Child of the Past" release should be marked as the latest release
