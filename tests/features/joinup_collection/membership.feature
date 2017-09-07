@api
Feature: Tests membership to Joinup collection.

  @joinup_collection
  Scenario: As newly registered user I'm automatically member of the 'Joinup'
    collection and I cannot leave.

    Given the following collections:
      | title                   | state     |
      | An arbitrary collection | validated |
    Given I am logged in as a user with the member role of the "An arbitrary collection" collection
    Then I am member of "Joinup" collection

    And I go to the homepage of the "An arbitrary collection" collection
    Then I should see the link "Leave this collection"

    Given I click "Leave this collection"
    When I press the "Confirm" button
    Then I should see the success message "You are no longer a member of An arbitrary collection."

    When I go to the homepage of the "Joinup" collection
    Then I should not see the link "Leave this collection"

    When I am about to leave the "Joinup" collection
    Then I should get an access denied error
