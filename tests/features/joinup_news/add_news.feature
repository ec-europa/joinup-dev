@api
Feature: "Add news" visibility options.
  In order to manage news
  As a collection member
  I need to be able to add news content through UI.

  Scenario: "Add news" button should only be shown to moderators.
    Given the following collection:
      | title | Ironman's home |
      | logo  | logo.png       |
    And the following solution:
      | title         | Ironman's room       |
      | description   | The room of ironman. |
      | documentation | text.pdf             |

    When I am logged in as a "moderator"
    And I go to the homepage of the "Ironman's home" collection
    Then I should see the link "Add news"
    When I go to the "Ironman's room" solution
    Then I should see the link "Add news"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Ironman's home" collection
    Then I should not see the link "Add news"
    When I go to the "Ironman's room" solution
    Then I should not see the link "Add news"

    When I am an anonymous user
    And I go to the homepage of the "Ironman's home" collection
    Then I should not see the link "Add news"
    When I go to the "Ironman's room" solution
    Then I should not see the link "Add news"

  Scenario: Add news as a moderator.
    Given the following collection:
      | title | Ironman's second house |
      | logo  | logo.png               |
    And the following solution:
      | title         | Ironman's basement                  |
      | description   | This is where experiments are done. |
      | documentation | text.pdf                            |
    And I am logged in as a moderator

    # Add news belonging to a collection
    When I go to the homepage of the "Ironman's second house" collection
    And I click "Add news"
    Then I should see the heading "Add news"
    And the following fields should be present "Headline, Kicker, Content"
    And the following fields should not be present "Groups audience"
    When I fill in the following:
      | Headline | We have some news    |
      | Kicker   | Some news kicker     |
      | Content  | The test is working! |
    And I press "Save"
    Then I should see the heading "We have some news"
    And I should see the success message "News We have some news has been created."
    And the "Ironman's second house" collection has a news page titled "We have some news"

    # Add news belonging to a solution
    When I go to the "Ironman's basement" solution
    And I click "Add news"
    Then I should see the heading "Add news"
    And the following fields should be present "Headline, Kicker, Content"
    And the following fields should not be present "Groups audience"
    When I fill in the following:
      | Headline | We have some news for the solution as well |
      | Kicker   | Some news for solution kicker              |
      | Content  | The solution test is working!              |
    And I press "Save"
    Then I should see the heading "We have some news for the solution as well"
    And I should see the success message "News We have some news for the solution as well has been created."
    And the "Ironman's basement" solution has a news page titled "We have some news for the solution as well"