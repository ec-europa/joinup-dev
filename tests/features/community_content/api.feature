@api
Feature: Create community content through the API
  In order to quickly generate community content for testing purposes
  As a developer for the Joinup platform
  I need to be able to create community content through the API

  Scenario Outline: Publication state of community content created through the API
    Given collection:
      | title | End user documentation |
      | state | validated              |
    And "<type>" content:
      | title   | body | collection             | field_state      |
      | <title> | body | End user documentation | <workflow state> |
    Then the community content with title "<title>" should have the publication state "<publication state>"

    Examples:
      | type       | workflow state   | publication state | title                    |
      | discussion | needs_update     | unpublished       | discussion-needs-update  |
      | discussion | proposed         | unpublished       | discussion-proposed      |
      | discussion | validated        | published         | discussion-validated     |
      | discussion | archived         | published         | discussion-archived      |
      | document   | draft            | unpublished       | document-draft           |
      | document   | needs_update     | unpublished       | document-needs-update    |
      | document   | proposed         | unpublished       | document-proposed        |
      | document   | validated        | published         | document-validated       |
      | document   | deletion_request | unpublished       | document-archived        |
      | event      | draft            | unpublished       | event-draft              |
      | event      | needs_update     | unpublished       | event-needs-update       |
      | event      | proposed         | unpublished       | event-proposed           |
      | event      | validated        | published         | event-validated          |
      | event      | deletion_request | unpublished       | event-archived           |
      | news       | draft            | unpublished       | news-draft               |
      | news       | needs_update     | unpublished       | news-needs-update        |
      | news       | proposed         | unpublished       | news-proposed            |
      | news       | validated        | published         | news-validated           |
      | news       | deletion_request | unpublished       | news-archived            |

