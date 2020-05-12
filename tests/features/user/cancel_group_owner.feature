@api @group-b
Feature:
  Accounts of group owners cannot be cancelled.

  Scenario: Canceling users that are the sole owners of groups cannot be done.
    Given users:
      | Username      | Roles | E-mail                   | First name | Family name |
      | Hazel Olson   |       | HazelOlson@example.com   | Hazel      | Olson       |
      | Amelia Barker |       | AmeliaBarker@example.com | Amelia     | Barker      |
    And collections:
      | title                   | state     |
      | Lugia was just released | validated |
      | Articuno is hunted      | validated |
    And solutions:
      | title                        | state     |
      | Random chat machine learning | validated |
    # Assign facilitator role in order to allow creation of a solution.
    # In UAT this can be done by creating the collection through the UI
    # with the related user.
    And the following collection user memberships:
      | collection              | user          | roles                      |
      | Lugia was just released | Hazel Olson   | administrator, facilitator |
      | Articuno is hunted      | Amelia Barker | administrator, facilitator |
    And the following solution user membership:
      | solution                     | user        | roles                      |
      | Random chat machine learning | Hazel Olson | administrator, facilitator |

    Given I am logged in as a moderator

    # Use the admin UI.
    When I click "People"
    And I check "Hazel Olson"
    And I check "Amelia Barker"
    And I select "Cancel the selected user account(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should not see the following lines of text:
      | This action cannot be undone.                |
      | When cancelling these accounts               |
      | Require email confirmation to cancel account |
      | Notify user when account is canceled         |
    But I should see the following lines of text:
      | User Hazel Olson cannot be deleted as they are currently the sole owner of these groups:   |
      | User Amelia Barker cannot be deleted as they are currently the sole owner of these groups: |
      | Collection                                                                                 |
      | Collections                                                                                |
      | Solution                                                                                   |
    And I should see the following links:
      | Lugia was just released      |
      | Articuno is hunted           |
      | Random chat machine learning |
      | Go back                      |
    And I should not see the button "Cancel accounts"

    # Use the user profile.
    When I go to "/admin/people"
    And I click "Hazel Olson"
    And I click "Edit" in the "Header" region
    And I press "Cancel account"
    Then I should not see the following lines of text:
      | This action cannot be undone.                |
      | When cancelling these accounts               |
      | Require email confirmation to cancel account |
      | Notify user when account is canceled         |
    But I should see the text "User Hazel Olson cannot be deleted as they are currently the sole owner of these groups:"
    And I should see the following links:
      | Lugia was just released      |
      | Random chat machine learning |
    And I should see the link "Go back"
    And I should not see the button "Cancel account"
