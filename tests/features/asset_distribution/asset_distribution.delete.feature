@api @group-a
Feature: Asset distribution deleting.
  In order to manage asset distributions
  As a solution owner or solution facilitator
  I need to be able to delete asset distributions through the UI.

  Scenario: "Delete" button should be shown to facilitators of the related solution.
    Given the following solutions:
      | title                 | description        | state     |
      | Rough valentine's day | Sample description | validated |
    And users:
      | Username   | E-mail                |
      | Papa Roach | paparoach@example.com |
    And the following solution user memberships:
      | solution              | user       | roles                      |
      | Rough valentine's day | Papa roach | administrator, facilitator |
    And the following distribution:
      | title       | Francesco's cats      |
      | description | Sample description    |
      | access url  | test.zip              |
      | parent      | Rough valentine's day |

    When I am logged in as a facilitator of the "Rough valentine's day" solution
    And I go to the homepage of the "Francesco's cats" asset distribution
    And I click "Edit"
    Then I should see the link "Delete"
    When I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | Papa Roach                                                                                        |
      | subject   | Joinup: A distribution has been deleted                                                           |
      | body      | The distribution Francesco's cats of the solution Rough valentine's day was successfully deleted. |
