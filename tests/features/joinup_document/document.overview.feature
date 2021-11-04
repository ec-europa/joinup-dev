@api @group-d
Feature: "Document" overview.
  In order to use documents
  As a user of the website
  I need to be able to interact with them.

  # A document tile redesign in ISAICP-3767 should show only title and description
  Scenario: Document tiles should show only title and description.
    Given the following licence:
      | title       | Beer licence                                     |
      | description | Offer a beer to the developer when you meet him. |
      | type        | Attribution                                      |
    And the following collection:
      | title | Traveller tools |
      | state | validated       |
    And document content:
      | title                      | document type | short title           | file type | file                                                        | body                                            | collection      | licence      | state     |
      | VAT refund sample document | document      | VAT refund fac-simile | upload    | text.pdf                                                    | Valid for people living outside the EU.         | Traveller tools | Beer licence | validated |
      | Local maps archive         | document      | Local maps            | remote    | https://github.com/ec-europa/joinup-dev/archive/develop.zip | Contains maps with the top locations in the EU. | Traveller tools | Beer licence | validated |

    When I go to the homepage of the "Traveller tools" collection
    Then I should see the "VAT refund sample document" tile
    And I should see the "Local maps archive" tile
    # The description should be visible
    And I should see the text "Valid for people living outside the EU."
    And I should see the text "Contains maps with the top locations in the EU."
    # Ensure previously displayed information is not visible any more
    But I should not see the text "text.pdf" in the "VAT refund sample document" tile
    And I should not see the text "Type: PDF" in the "VAT refund sample document" tile
    And I should not see the text "Size: 1.08 KB" in the "VAT refund sample document" tile
    # Same criteria should be valid for the external type of document as well
    And I should not see the text "Type: EXTERNAL" in the "Local maps archive" tile
    And I should not see the text "Size" in the "Local maps archive" tile
    And I should not see the text "develop.zip" in the "Local maps archive" tile
