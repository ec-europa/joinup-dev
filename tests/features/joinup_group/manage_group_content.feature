@api @group-b
Feature: As moderator I want to be able to move content between groups.

  Scenario: Test the manage group content UI.

    Given the following collections:
      | title        | state     |
      | Vintage Art  | validated |
      | Decadent Art | validated |
    And the following solutions:
      | title | collection | state |
      | Picture conservation | Vintage Art  | validated |
      | Art Therapy          | Decadent Art | validated |
    And document content:
      | title             | document type | collection  | state     |
      | The Panama Papers | Document      | Vintage Art | validated |
      | The Area 51 File  | Document      | Vintage Art | draft     |
    And discussion content:
      | title               | collection  | state     |
      | The Ultimate Debate | Vintage Art | validated |
    And event content:
      | title                    | collection  | state     |
      | Version 2.0 Launch Party | Vintage Art | validated |
    And news content:
      | title                              | collection  | state     |
      | Exports Leap Despite Currency Gain | Vintage Art | validated |
    And custom_page content:
      | title                | collection  |
      | HOWTOs               | Vintage Art |
      | Looking for Support? | Vintage Art |
    And the following custom page menu structure:
      | title                | parent | weight |
      | Looking for Support? | HOWTOs | 1      |

    Given I am an anonymous user
    And I go to the homepage of the "Vintage Art" collection
    Then I should not see the link "Manage content"

    Given I am logged in as an "authenticated user"
    And I go to the homepage of the "Vintage Art" collection
    Then I should not see the link "Manage content"

    Given I am logged in as a facilitator of the "Vintage Art" collection
    And I go to the homepage of the "Vintage Art" collection
    Then I should not see the link "Manage content"

    Given I am logged in as a moderator

    # Visit one of the pages to warm up the render caches. Later, we'll check
     # that no stale cached content is being shown.
    When I go to the homepage of the "Vintage Art" collection
    And I click "HOWTOs" in the "Navigation menu block" region
    Then I should see the heading "Vintage Art"

    # Start the actual test scenario as a moderator.
    When I go to the homepage of the "Vintage Art" collection
    Then I should see the link "Manage content"
    Given I click "Manage content"
    Then I should see "Manage content" in the Header

    # Download the list as CSV.
    When I click "Download CSV"
    Then the response should contain "Type;Title;\"Created on\";\"Last update\";URL"
    Then the response should contain "Solution;\"Picture conservation\";"
    Then the response should contain "/collection/vintage-art/solution/picture-conservation"
    And the response should contain "Document;\"The Panama Papers\";"
    And the response should contain "/collection/vintage-art/document/panama-papers"
    And the response should contain "Discussion;\"The Ultimate Debate\";"
    And the response should contain "/collection/vintage-art/discussion/ultimate-debate"
    And the response should contain "Event;\"Version 2.0 Launch Party\";"
    And the response should contain "/collection/vintage-art/event/version-20-launch-party"
    And the response should contain "News;\"Exports Leap Despite Currency Gain\";"
    And the response should contain "/collection/vintage-art/news/exports-leap-despite-currency-gain"
    And the response should contain "\"Custom page\";HOWTOs;"
    And the response should contain "/collection/vintage-art/howtos"
    And the response should contain "\"Custom page\";\"Looking for Support?\";"
    And the response should contain "/collection/vintage-art/looking-support"
    # Unpublished content is not exported.
    And the response should not contain "Document;\"The Area 51 File\";"
    And the response should not contain "/collection/vintage-art/document/area-51-file"
    # Content in different groups is not exported.
    And the response should not contain "Solution;\"Art Therapy\";"
    And the response should not contain "/collection/decadent-art/document/art-therapy"

    When I go to the homepage of the "Vintage Art" collection
    Then I should see the link "Manage content"
    Given I click "Manage content"
    Then I should see "Manage content" in the Header
