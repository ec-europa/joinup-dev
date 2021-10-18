@api @group-d
Feature:
  As an owner of the website
  In order for users to easier navigate in the website
  The local actions should be helpful in entities.

  Scenario: The 'View' local action does not show in the canonical path.
    Given I am logged in as a moderator

    Given collection:
      | title | Test collection |
      | state | validated       |
    When I go to the "Test collection" collection
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Metadata" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region

    Given solution:
      | title      | Test solution   |
      | collection | Test collection |
      | state      | validated       |
    When I go to the "Test solution" solution
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Metadata" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region

    Given release:
      | title         | Test release  |
      | is version of | Test solution |
      | state         | validated     |
    When I go to the "Test release" release
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Metadata" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region

    Given distribution:
      | title  | Test distribution |
      | parent | Test release      |
    When I go to the "Test distribution" distribution
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Metadata" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region

    Given licence:
      | title | Test licence |
    When I go to the "Test licence" licence
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Metadata" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region

    Given discussion content:
      | title           | collection      | state     |
      | Test discussion | Test collection | validated |
    When I go to the "Test discussion" discussion
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Delete" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region

    Given document content:
      | title         | collection      | state     |
      | Test document | Test collection | validated |
    When I go to the "Test document" document
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Delete" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region

    Given event content:
      | title      | collection      | state     |
      | Test event | Test collection | validated |
    When I go to the "Test event" event
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Delete" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region

    Given news content:
      | title     | collection      | state     |
      | Test news | Test collection | validated |
    When I go to the "Test news" news
    Then I should not see the link "View" in the "Entity actions" region
    When I click "Delete" in the "Entity actions" region
    Then I should see the link "View" in the "Entity actions" region
