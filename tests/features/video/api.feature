@api
Feature: Video API
  In order to manage videos programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Video" bundle

  # This is a temporary test until we have proper support for videos.
  # The machine names are used but should be replaced with proper names once the video functionality is implemented.
  Scenario: Programmatically create a Video entity in a collection
    Given the following owner:
      | name         |
      | Video editor |
    And the following collection:
      | title             | Video library collection |
      | owner             | Video editor             |
      | logo              | logo.png                 |
      | moderation        | yes                      |
      | elibrary creation | facilitators             |
      | state             | validated                |
    And video content:
      | title                     | body        | field_video                                 | og_audience              |
      | Dummy Video in collection | Dummy text. | https://www.youtube.com/watch?v=uLcS7uIlqPo | Video library collection |
    Then I should have a "Video" page titled "Dummy Video in collection"

    # Regression test for asserting that the video should appear as a tile.
    # @todo: This should move to the appropriate test once the video functionality is implemented.
    When I go to the homepage of the "Video library collection" collection
    Then I should see the "Dummy Video in collection" tile

  Scenario: Programmatically create a Video entity in a solution
    Given the following owner:
      | name         |
      | Video editor |
    And the following solution:
      | title             | Video library solution |
      | owner             | Video editor           |
      | logo              | logo.png               |
      | moderation        | yes                    |
      | elibrary creation | facilitators           |
      | state             | validated              |
    And video content:
      | title                   | body        | field_video                                 | og_audience            |
      | Dummy Video in solution | Dummy text. | https://www.youtube.com/watch?v=uLcS7uIlqPo | Video library solution |
    Then I should have a "Video" page titled "Dummy Video in solution"

    # Regression test for asserting that the video should appear as a tile.
    # @todo: This should move to the appropriate test once the video functionality is implemented.
    When I go to the homepage of the "Video library solution" solution
    Then I should see the "Dummy Video in solution" tile
