@api @group-e
Feature:
  Check downloads counter's value at different places.

  @clearStaticCache
  Scenario: Check solution downloads counter
    # Create the dummy data to work with.
    Given the following collection:
      | title | Ocean studies |
    Given the following solution:
      | title       | Climate change tracker                            |
      | description | Atlantic salmon arrived after the Little Ice Age. |
      | collection  | Ocean studies                                     |
      | state       | validated                                         |
    Given the following distributions:
      | title                 | description          | parent                 | downloads |
      | Sample distribution   | Sample description   | Climate change tracker | 10        |
      | Sample distribution 2 | Sample description 2 | Climate change tracker | 0         |
    # Checks solutions tiles to we have the correct numbers.
    When I visit the solution overview
    Then the "Climate change tracker" tile should show 10 downloads
    When I go to the homepage of the "Climate change tracker" solution
    # Check the same thing on the homepage of the solutions.
    Then I should see the text "Downloads: 10"
    When I delete the "Sample distribution" asset distribution
    And I reload the page
    # Check it again after the first distribution is deleted and cache has been cleared.
    Then I should not see the text "Downloads: 10"
    # Go back to the solution tiles page and check downloads are disappeared.
    When I visit the solution overview
    Then the download icon should not be shown in the "Climate change tracker" tile
    Given the following distribution:
      | title       | Sample distribution 3  |
      | description | Sample description 3   |
      | parent      | Climate change tracker |
      | downloads   | 20                     |
    When I reload the page
    Then the "Climate change tracker" tile should show 20 downloads
