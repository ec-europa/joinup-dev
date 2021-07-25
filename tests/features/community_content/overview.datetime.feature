@api
Feature:
  As an owner of the website
  In order for my visitor to have more precise information on the actions taken on content
  I want to have publication and update dates presented in the overview of my content.

  Scenario Outline: Last update date shown for specific content bundles.
    Given the following community:
      | title | Gravitational wave detectors |
      | state | validated                    |
    And <type> content:
      | title | created                         | <publication field name>        | changed                         | community                   | state     |
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
    Given I am logged in as a facilitator of the "Gravitational wave detectors" community
    When I go to the homepage of the "Gravitational wave detectors" community
    And I click the contextual link "Pin" in the "LIGO" tile
    Then I should see the success message "LIGO has been pinned in the community Gravitational wave detectors."
    When I go to the "LIGO" <type>
    Then I should see the text "Published on: 25/12/2019"
    And I should see the text "Last update: 26/12/2019"

    When I go to the homepage of the "Gravitational wave detectors" community
    And I click the contextual link "Unpin" in the "LIGO" tile
    Then I should see the success message "LIGO has been unpinned in the community Gravitational wave detectors."
    When I go to the "LIGO" <type>
    Then I should see the text "Published on: 25/12/2019"
    And I should see the text "Last update: 26/12/2019"

    # Featuring and unfeaturing also should not change the update timestamp.
    Given I am logged in as a moderator
    When I go to the homepage of the "Gravitational wave detectors" community
    And I click the contextual link "Feature" in the "LIGO" tile
    Then I should see the success message "LIGO has been set as featured content."
    When I go to the "LIGO" <type>
    Then I should see the text "Published on: 25/12/2019"
    And I should see the text "Last update: 26/12/2019"

    When I go to the homepage of the "Gravitational wave detectors" community
    And I click the contextual link "Remove from featured" in the "LIGO" tile
    Then I should see the success message "LIGO has been removed from the featured contents."
    When I go to the "LIGO" <type>
    Then I should see the text "Published on: 25/12/2019"
    And I should see the text "Last update: 26/12/2019"

    Examples:
      # Events are excluded since they do not show the updated timestamp to the end user.
      | type       | publication field name    |
      | discussion | publication date          |
      | document   | document publication date |
      | news       | publication date          |

  @terms
  Scenario: Documents without a publication date should show the published_at property.
    Given the following community:
      | title | Gravitational pull detectors |
      | state | validated                    |
    When I am logged in as a facilitator of the "Gravitational pull detectors" community
    And I go to the "Gravitational pull detectors" community
    And I click "Add document"
    And I fill in the following:
      | Title       | POLI |
      | Short title | POLI |
    And I select "Document" from "Type"
    And I select "Supplier exchange" from "Topic"
    # Regression test: Document is successfully displayed even when a publication date is not set.
    And I clear the date of the "Publication date" widget
    And I clear the time of the "Publication date" widget
    Then I upload the file "test.zip" to "Upload a new file or enter a URL"
    When I enter "Blah blah nobody cares." in the "Description" wysiwyg editor
    And I press "Publish"
    Then I should see the heading "POLI"
    And I should see text matching "Published on: \d{2}/\d{2}/\d{4}"
    # We do not see the "Last update:" because the "Published on:" text appears and is the same as the "Last update:"
    # since it was just created.
    And I should not see the text "Last update:" in the "Content" region

  @terms
  Scenario: Draft documents without a publication date should only show the last updated time.
    Given the following community:
      | title | Gravitational pull detectors |
      | state | validated                    |
    When I am logged in as a facilitator of the "Gravitational pull detectors" community
    And I go to the "Gravitational pull detectors" community
    And I click "Add document"
    And I fill in the following:
      | Title       | POLI |
      | Short title | POLI |
    And I select "Document" from "Type"
    And I select "Supplier exchange" from "Topic"
    # Regression test: Document is successfully displayed even when a publication date is not set.
    And I clear the date of the "Publication date" widget
    And I clear the time of the "Publication date" widget
    Then I upload the file "test.zip" to "Upload a new file or enter a URL"
    When I enter "Blah blah nobody cares." in the "Description" wysiwyg editor
    And I press "Save as draft"
    Then I should see the heading "POLI"
    # The "Published on" is empty and the Last update points to the current date.
    And I should see text matching "Published on\: Last update\: \d{2}/\d{2}/\d{4}"
