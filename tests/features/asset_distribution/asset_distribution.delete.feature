@api
Feature: Asset distribution deleting.
  In order to manage asset distributions
  As a solution owner or solution facilitator
  I need to be able to delete asset distributions through the UI.
  
  Scenario: "Delete" button should be shown to facilitators of the related solution.
    Given the following solutions:
      | title                 | description        | state     |
      | Rough valentine's day | Sample description | validated |
    And the following distribution:
      | title       | Francesco's cats      |
      | description | Sample description    |
      | file        | test.zip              |
      | solution    | Rough valentine's day |

    When I am logged in as a facilitator of the "Rough valentine's day" solution
    And I go to the homepage of the "Francesco's cats" asset distribution
    And I click "Edit"
    Then I should see the button "Delete"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Francesco's cats" asset distribution
    And I click "Edit"
    Then I should not see the button "Delete"

    When I am an anonymous user
    And I go to the homepage of the "Francesco's cats" asset distribution
    And I click "Edit"
    Then I should not see the button "Delete"
