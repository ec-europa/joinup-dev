@api @javascript
Feature: Password management
  A user must be able to change his password

  Scenario: A logged-in user can navigate to his profile and change his password.
    Given users:
      | name           | mail                        | pass        | First name | Family name |
      | Charlie Change | charlie.change@example.com  | changeme    | Charlie    | Change      |
    When I am logged in as "Charlie Change"
    And I am on the homepage
    Then I click "My account"
    Then I click "Edit"
    Then I fill in "Current password" with "changeme"
    Then I fill in "Password" with "NewPass"
    Then I fill in "Confirm password" with "NewPass"
    And I press the "Save" button
    Then I should see the success message "The changes have been saved."

  Scenario: A user can request a one-time-login link.
    Given users:
      | name       | mail                    |
      | Alz Heimer | alz.heimer@example.com  |
    When I am an anonymous user
    And I am on the homepage
    Then I click "Log in"
    And I click "Reset your password"
    And I fill in "Username or email address" with "Alz Heimer"
    Then I press the "Submit" button
    Then I should see the success message "Further instructions have been sent to your email address."
