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
      | discussion | in_assessment    | unpublished       | discussion-in-assessment |
      | discussion | proposed         | unpublished       | discussion-proposed      |
      | discussion | validated        | published         | discussion-validated     |
      | discussion | archived         | published         | discussion-archived      |
      | document   | draft            | unpublished       | document-draft           |
      | document   | in_assessment    | unpublished       | document-in-assessment   |
      | document   | proposed         | unpublished       | document-proposed        |
      | document   | validated        | published         | document-validated       |
      | document   | deletion_request | unpublished       | document-archived        |
      | event      | draft            | unpublished       | event-draft              |
      | event      | in_assessment    | unpublished       | event-in-assessment      |
      | event      | proposed         | unpublished       | event-proposed           |
      | event      | validated        | published         | event-validated          |
      | event      | deletion_request | unpublished       | event-archived           |
      | news       | draft            | unpublished       | news-draft               |
      | news       | in_assessment    | unpublished       | news-in-assessment       |
      | news       | proposed         | unpublished       | news-proposed            |
      | news       | validated        | published         | news-validated           |
      | news       | deletion_request | unpublished       | news-archived            |

