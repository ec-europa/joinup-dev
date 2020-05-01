@api
Feature:
  As a site builder of the site
  In order to be able to better control the structure of my content
  I need to be able to place paragraphs for content.

  @javascript
  Scenario: Paragraph sections are multivalue and sort-able.
    Given the following collection:
      | title | Paragraphs collection |
      | state | validated             |
    When I am logged in as a facilitator of the "Paragraphs collection" collection
    And I go to the "Paragraphs collection" collection
    And I open the plus button menu
    And I click "Add custom page"
    And I fill in "Title" with "Paragraphs page"
    And I press "Save"
    Then I should see the heading "Paragraphs page"

    When I open the header local tasks menu
    And I click "Edit"
    # The first paragraphs item is open by default.
    Then there should be 1 row in the "Custom page body" field
    Then I should see the "Remove" button in the "Custom page body" field at row 1

    Given I press "Remove" in the "Custom page body" field at row 1
    Then I should see the "Confirm removal" button in the "Custom page body" field at row 1
    And I should see the "Restore" button in the "Custom page body" field at row 1
    But I should not see the "Remove" button in the "Custom page body" field at row 1

    Given I press "Confirm removal" in the "Custom page body" field at row 1
    Then there should be 0 rows in the "Custom page body" field

    Given I press "Add Simple paragraph" in the "Custom page body" paragraphs field
    Then there should be 1 row in the "Custom page body" field

    Given I press "Add Simple paragraph" in the "Custom page body" paragraphs field
    Then there should be 2 rows in the "Custom page body" field

    When I enter "AAAAAAAAAA" in the "Body" wysiwyg editor in the "Custom page body" field at position 1
    And I enter "BBBBBBBBBB" in the "Body" wysiwyg editor in the "Custom page body" field at position 2

    And I drag the table row at position 2 up
    And I press "Save"
    Then there should be 2 paragraphs in the page
    And I should see the following paragraphs in the given order:
      | BBBBBBBBBB |
      | AAAAAAAAAA |
