@api @email
Feature:
  In order to efficiently mange users
  As a moderator of the website
  I need to be able to cancel user accounts

  Background:
    Given users:
      | Username      | Roles     | E-mail                   |
      | Robin Kelley  | Moderator | RobinKelley@example.com  |
      | Hazel Olson   |           | HazelOlson@example.com   |
      | Amelia Barker |           | AmeliaBarker@example.com |
      | Alicia Potter |           | AliciaPotter@example.com |
    And collections:
      | title                           | state     |
      | Lugia was just released         | validated |
      | Articuno is hunted              | validated |
      | Moltress is nowhere to be found | validated |
    # Assign facilitator role in order to allow creation of a solution.
    # In UAT this can be done by creating the collection through the UI
    # with the related user.
    And the following collection user memberships:
      | collection                      | user          | roles                      |
      | Lugia was just released         | Hazel Olson   | administrator, facilitator |
      | Articuno is hunted              | Amelia Barker | administrator, facilitator |
      | Moltress is nowhere to be found | Alicia Potter | administrator, facilitator |

  Scenario: Cancel a single user account as a moderator.
    When I am logged in as a moderator
    And I click "People"
    And I check "Hazel Olson"
    And I check "Amelia Barker"
    And I select "Cancel the selected user account(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the text "User Hazel Olson cannot be deleted as it is currently the sole owner of these collections:"
    And I should see the text "User Amelia Barker cannot be deleted as it is currently the sole owner of these collections:"
    And I should see the link "Lugia was just released"
    And I should see the link "Articuno is hunted"
