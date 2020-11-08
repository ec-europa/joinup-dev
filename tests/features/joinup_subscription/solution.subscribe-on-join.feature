@api
Feature: Subscribing to a collection after joining
  In order to promote my collection
  As a collection owner
  I want to persuade new members to subscribe to my collection

  @javascript
  Scenario: Show a modal dialog asking a user to subscribe after joining
    Given collection:
      | title       | Some parent collection |
      | abstract    | Abstract               |
      | description | Description            |
      | closed      | yes                    |
      | state       | validated              |
    And solution:
      | title      | Some solution to subscribe |
      | state      | validated                  |
      | collection | Some parent collection     |
    And users:
      | Username          |
      | Cornilius Darcias |

    When I am logged in as "Cornilius Darcias"
    And I go to the "Some solution to subscribe" solution
    Then I should see the button "Follow this solution"

    When I press "Follow this solution"
    Then I should see the success message "You are now following this solution."
