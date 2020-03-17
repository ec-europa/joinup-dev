@api
Feature:
  As an owner of the website
  In order for my visitor to have more precise information on the actions taken on content
  I want to have publication and update dates presented in the overview of my content.

  Scenario Outline: Last update date shown for specific content bundles.
    Given the following collection:
      | title | Some collection |
      | state | validated       |
    Given <type> content:
      | title        | created                         | <publication field name>        | changed                         | collection      | state     |
      | Some content | Wed, 25 Dec 2019 13:00:00 +0100 | Wed, 25 Dec 2019 14:00:00 +0100 | Wed, 25 Dec 2019 15:00:00 +0100 | Some collection | validated |

    When I go to the "Some content" <type>
    Then I should see the text "Published on: 25/12/2019"
    # The last update is not visible if the date is the same even if the time is different.
    And I should not see the text "Last update: 25/12/2019"

    # Avoid having to fill in different mandatory fields for different bundles by directly updating the entity.
    Given the changed date of the "Some content" <type> is "Thu, 26 Dec 2019 14:00:00 +0100"
    When I go to the "Some content" <type>
    Then I should see the text "Published on: 25/12/2019"
    And I should see the text "Last update: 26/12/2019"

    Examples:
      | type       | publication field name    |
      | discussion | publication date          |
      | document   | document publication date |
      | news       | publication date          |
