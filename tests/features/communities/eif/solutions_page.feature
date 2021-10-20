@api @eif_community @terms @clearStaticCache @group-a
Feature: As a user, visiting the EIF Toolbox page, I want to be able to filter
  the page by EIF category.

  Scenario: Test the EIF Toolbox solution page.

    Given owner:
      | name | type    |
      | ACME | Company |
    And collection:
      | title | Parent    |
      | state | validated |
    And solutions:
      | title      | eif reference                      | eif category    | collection | state     |
      | Solution 1 | Recommendation 1, Recommendation 2 | Common services | Parent     | validated |

    When I go to "/collection/nifo-collection/solution/eif-toolbox/solutions"
    Then I should see the following tiles in the correct order:
      | Solution 1 |

    # The navigator shows only when there are more than one category.
    And I should not see the link "All"
    And I should not see the link "Assessment tools"
    And I should not see the link "Common frameworks"
    And I should not see the link "Common services"
    And I should not see the link "Generic tools"
    And I should not see the link "Legal interoperability tools"
    And I should not see the link "Semantic assets"

    Given solutions:
      | title      | eif reference    | eif category                      | collection | state     | owner |
      | Solution 2 | Recommendation 4 | Common services, Assessment tools | Parent     | validated |       |
      | Solution 3 |                  |                                   | Parent     | validated | ACME  |

    When I reload the page
    Then the page should not be cached
    # Adding a solution invalidates the page cache.
    But I reload the page
    And the page should be cached
    Then I should see the following tiles in the correct order:
      | Solution 1 |
      | Solution 2 |
    And I should see the link "All"
    And I should see the link "Assessment tools"
    And I should see the link "Common services"
    And I should not see the link "Common frameworks"
    And I should not see the link "Generic tools"
    And I should not see the link "Legal interoperability tools"
    And I should not see the link "Semantic assets"

    When I click "Assessment tools"
    Then I should see the following tiles in the correct order:
      | Solution 2 |
    # The 'Solutions' left-menu link is highlighted even on a category page.
    And the menu link "Solutions" is in the active trail
    But I click "Common services"
    Then I should see the following tiles in the correct order:
      | Solution 1 |
      | Solution 2 |
    And the menu link "Solutions" is in the active trail
    When I click "All"
    Then I should see the following tiles in the correct order:
      | Solution 1 |
      | Solution 2 |
    And the menu link "Solutions" is in the active trail

    Given I am logged in as a moderator
    When I go to the edit form of the "Solution 3" solution
    And I select "Recommendation 7" from "EIF reference"
    And I check the box "Semantic assets"
    # Other require fields...
    And I fill in "Description" with "foo"
    And I fill in "Name" with "TikTok"
    And I fill in "E-mail address" with "where@example.com"
    And I select "E-health" from "Topic"
    And I select "Public Policy Cycle" from "Solution type"
    And I press "Publish"

    When I go to "/collection/nifo-collection/solution/eif-toolbox/solutions"
    # Updating a solution invalidates the page cache.
    Then the page should not be cached
    But I reload the page
    And the page should be cached
    And I should see the following tiles in the correct order:
      | Solution 1 |
      | Solution 2 |
      | Solution 3 |
    And I should see the link "All"
    And I should see the link "Assessment tools"
    And I should see the link "Common services"
    And I should see the link "Semantic assets"
    And I should not see the link "Common frameworks"
    And I should not see the link "Generic tools"
    And I should not see the link "Legal interoperability tools"

    When I delete the "Solution 3" solution
    # Deleting a solution invalidates the page cache.
    And I reload the page
    Then the page should not be cached
    But I reload the page
    And the page should be cached
    And I should see the following tiles in the correct order:
      | Solution 1 |
      | Solution 2 |
    And I should see the link "All"
    And I should see the link "Assessment tools"
    And I should see the link "Common services"
    And I should not see the link "Common frameworks"
    And I should not see the link "Generic tools"
    And I should not see the link "Legal interoperability tools"
    And I should not see the link "Semantic assets"

    # Test the pager.
    Given solutions:
      | title       | eif reference    | eif category    | collection | state     |
      | Solution 3  | Recommendation 4 | Common services | Parent     | validated |
      | Solution 4  | Recommendation 4 | Common services | Parent     | validated |
      | Solution 5  | Recommendation 4 | Common services | Parent     | validated |
      | Solution 6  | Recommendation 4 | Common services | Parent     | validated |
      | Solution 7  | Recommendation 4 | Common services | Parent     | validated |
      | Solution 8  | Recommendation 4 | Common services | Parent     | validated |
      | Solution 9  | Recommendation 4 | Common services | Parent     | validated |
      | Solution 10 | Recommendation 4 | Common services | Parent     | validated |
      | Solution 11 | Recommendation 4 | Common services | Parent     | validated |
      | Solution 12 | Recommendation 4 | Common services | Parent     | validated |
      | Solution 13 | Recommendation 4 | Common services | Parent     | validated |
      | Solution 14 | Recommendation 4 | Common services | Parent     | validated |

    When I reload the page
    Then I should see the following tiles in the correct order:
      | Solution 1  |
      | Solution 2  |
      | Solution 3  |
      | Solution 4  |
      | Solution 5  |
      | Solution 6  |
      | Solution 7  |
      | Solution 8  |
      | Solution 9  |
      | Solution 10 |
      | Solution 11 |
      | Solution 12 |
    And I should see the link "Current page 1"
    And I should see the link "Page 2"
    And I should see the link "Next page"
    And I should see the link "Last page"

    When I click "Next page"
    Then I should see the following tiles in the correct order:
      | Solution 13 |
      | Solution 14 |
    And I should see the link "First page"
    And I should see the link "Previous page"
    And I should see the link "Page 1"
    And I should see the link "Current page 2"
    And I delete the "TikTok" contact information

  @javascript
  Scenario: Test the recommendation selector.
    When I go to "/collection/nifo-collection/solution/eif-toolbox/solutions"
    Then the option with text "Filter Solutions by Recommendation" from select "Jump to recommendation" is selected
    And I select "Solutions implementing Recommendation 17" from "Jump to recommendation"
    Then I should see the heading "Recommendation 17"

  Scenario: Test that links behave like a normal menu.
    Given collection:
      | title | Parent    |
      | state | validated |
    And solutions:
      | title      | eif reference    | eif category     | collection | state     |
      | Solution 1 | Recommendation 4 | Assessment tools | Parent     | validated |
      | Solution 2 | Recommendation 4 | Common services  | Parent     | validated |

    When I am an anonymous user
    And I go to "/collection/nifo-collection/solution/eif-toolbox/solutions"
    Then "All" should be the active item in the "Content" region

    When I click "Common services"
    Then "Common services" should be the active item in the "Content" region
