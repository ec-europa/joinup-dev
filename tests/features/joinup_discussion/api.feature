@api @group-d
Feature: Discussion API
  In order to manage discussion entities programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Discussion" bundle

  Scenario: Programmatically create a discussion
    And the following collection:
      | title | Parallel programming |
      | state | validated            |
    Given discussion content:
      | title                | content                  | collection           | state     |
      | Fearless concurrency | Let us have a discussion | Parallel programming | validated |
    Then I should have a "Discussion" page titled "Fearless concurrency"

    # Check that the basic fields are visible.
    When I go to the "Fearless concurrency" discussion
    Then I should see the heading "Parallel programming"
    And I should see the heading "Fearless concurrency"
    And I should see the text "Let us have a discussion"
    # The discussion does not have a file attached to it, so it should not show
    # the heading of the attachments list.
    But I should not see the text "Attachments"
