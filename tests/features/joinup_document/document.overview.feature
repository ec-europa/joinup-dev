@api
Feature: "Document" overview.
  In order to use documents
  As a user of the website
  I need to be able to interact with them.

  Scenario: Document type and size should be visible.
    Given the following collection:
      | title | Traveller tools |
      | state | validated       |
    And document content:
      | title                      | type     | short title           | file type | file                                                        | body                                            | collection      |
      | VAT refund sample document | document | VAT refund fac-simile | upload    | text.pdf                                                    | Valid for people living outside the EU.         | Traveller tools |
      | Local maps archive         | document | Local maps            | remote    | https://github.com/ec-europa/joinup-dev/archive/develop.zip | Contains maps with the top locations in the EU. | Traveller tools |

    When I go to the homepage of the "Traveller tools" collection
    Then I should see the "VAT refund sample document" tile
    And I should see the "Local maps archive" tile
    # A document that contains an uploaded file should show the type and
    # the weight of it.
    And I should see the text "Type: PDF" in the "VAT refund sample document" tile
    And I should see the text "Weight: 1.08 KB" in the "VAT refund sample document" tile
    # A document that contains a remote file URI should show the type only.
    And I should see the text "Type: ZIP" in the "Local maps archive" tile
    But I should not see the text "Weight" in the "Local maps archive" tile
