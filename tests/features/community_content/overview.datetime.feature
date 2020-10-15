@api
Feature:
  As an owner of the website
  In order for my visitor to have more precise information on the actions taken on content
  I want to have publication and update dates presented in the overview of my content.

  Scenario Outline: Last update date shown for specific content bundles.
    Given the following collection:
      | title | Gravitational wave detectors |
      | state | validated                    |
    Given <type> content:
      | title | created                         | <publication field name>        | changed                         | collection                   | state     |
      | LIGO  | Wed, 25 Dec 2019 13:00:00 +0100 | Wed, 25 Dec 2019 14:00:00 +0100 | Wed, 25 Dec 2019 15:00:00 +0100 | Gravitational wave detectors | validated |

    When I go to the "LIGO" <type>
    Then I should see the text "Published on: 25/12/2019"
    # The last update is not visible if the date is the same even if the time is different.
    And I should not see the text "Last update: 25/12/2019"

    # Avoid having to fill in different mandatory fields for different bundles by directly updating the entity.
    Given the changed date of the "LIGO" <type> is "Thu, 26 Dec 2019 14:00:00 +0100"
    When I go to the "LIGO" <type>
    Then I should see the text "Published on: 25/12/2019"
    And I should see the text "Last update: 26/12/2019"

    # Pinning and unpinning the content should not change the update timestamp.
    Given I am logged in as a facilitator of the "Gravitational wave detectors" collection
    When I go to the homepage of the "Gravitational wave detectors" collection
    And I click the contextual link "Pin" in the "LIGO" tile
    Then I should see the success message "LIGO has been pinned in the collection Gravitational wave detectors."
    When I go to the "LIGO" <type>
    Then I should see the text "Published on: 25/12/2019"
    And I should see the text "Last update: 26/12/2019"

    When I go to the homepage of the "Gravitational wave detectors" collection
    And I click the contextual link "Unpin" in the "LIGO" tile
    Then I should see the success message "LIGO has been unpinned in the collection Gravitational wave detectors."
    When I go to the "LIGO" <type>
    Then I should see the text "Published on: 25/12/2019"
    And I should see the text "Last update: 26/12/2019"

    Examples:
      # Events are excluded since they do not show the updated timestamp to the end user.
      | type       | publication field name    |
      | discussion | publication date          |
      | document   | document publication date |
      | news       | publication date          |
