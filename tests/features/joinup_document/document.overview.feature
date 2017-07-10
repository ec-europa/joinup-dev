@api
Feature: "Document" overview.
  In order to use documents
  As a user of the website
  I need to be able to interact with them.

  Scenario: File type and size should be visible in the document tiles.
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
    # A document that contains an uploaded file should show the file name,
    # type and the weight of it.
    And I should see the text "text.pdf" in the "VAT refund sample document" tile
    And I should see the text "Type: PDF" in the "VAT refund sample document" tile
    And I should see the text "Size: 1.08 KB" in the "VAT refund sample document" tile
    And the download link is shown in the "VAT refund sample document" document tile
    # A document that contains a remote file URI should mark the type as external.
    And I should see the text "Type: EXTERNAL" in the "Local maps archive" tile
    But I should not see the text "Size" in the "Local maps archive" tile
    And I should not see the text "develop.zip" in the "Local maps archive" tile
    And the download link is shown in the "Local maps archive" document tile
