@api
Feature: Collection TCA agreement
  In order to ensure activity by facilitators
  As a site owner
  I want users to sign the TCA agreement before proposing a collection.

  Background:
    # The 'Create collection' button is not shown if there is no collection available.
    Given the following collection:
      | title | TCA Agreement collection |
      | logo  | logo.png                 |
      | state | validated                |

  Scenario: TCA agreement page is not accessible by anonymous users.
    When I am not logged in
    And I visit "/collections"
    And I click "Create collection"
    Then I should see the error message "Access denied. You must sign in to view this page."

  Scenario: Authenticated users can access the TCA agreement page.
    When I am logged in as a user with the "authenticated" role
    And I visit "/collections"
    And I click "Create collection"
    Then I should see the heading "Why create a Collection?"
    When I press "No thanks"
    Then the url should match "/collections"

    When I click "Create collection"
    And I press "Yes"
    # No javascript test.
    Then I should see the error message "You have to agree that you will manage your collection on a regular basis."
    When I check the box "I understand and I commit to manage my collection on a regular basis."
    And I press "Yes"
    Then I should see the heading "Propose collection"

