@api @group-g
Feature: Video API
  In order to manage videos programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Video" bundle

  # This is a temporary test until we have proper support for videos.
  # The machine names are used but should be replaced with proper names once the video functionality is implemented.
  Scenario Outline: Programmatically create a Video entity
    Given the following owner:
      | name         |
      | Video editor |
    And the following <group type>:
      | title            | Video library            |
      | owner            | Video editor             |
      | logo             | logo.png                 |
      | moderation       | yes                      |
      | content creation | facilitators and authors |
      | state            | validated                |
    And video content:
      | title       | body        | field_video                                 | og_audience   |
      | Dummy Video | Dummy text. | https://www.youtube.com/watch?v=uLcS7uIlqPo | Video library |
    Then I should have a "Video" page titled "Dummy Video"

    # Regression test for asserting that the video should appear as a tile.
    # @todo: This should move to the appropriate test once the video functionality is implemented.
    When I go to the homepage of the "Video library" <group type>
    Then I should see the "Dummy Video" tile
    Examples:
      | group type |
      | collection |
      | solution   |
