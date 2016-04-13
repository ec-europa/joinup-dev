@api
Feature: User profile
  A user must be able to change his own profile.
  A moderator must be able to edit any user account.

  @javascript
  Scenario: A logged-in user can navigate to his own profile and edit it.
    Given users:
      | name              | mail         | roles        |
      | Leonardo Da Vinci | foo@bar.com  |              |
    When I am logged in as "Leonardo Da Vinci"
    And I am on the homepage
    Then I click "My account"
    Then I click "Edit"
    Then the following fields should be present "Current password, Email address, Password, Confirm password, First name"
    Then the following fields should be present "Family name, Photo, Nationality, Professional domain"
    Then the following fields should not be present "Time zone"
    And I fill in "First name" with "Leoke"
    And I fill in "Family name" with "di ser Piero da Vinci"
    And I press the "Save" button
    Then I should see the success message "The changes have been saved."
    Then I click 'View'
    And I should see the text "Leoke"
    And I should see the text "di ser Piero da Vinci"

  Scenario: A moderator can navigate to any users profile and edit it.
    Given users:
      | name              | mail                   | roles        |
      | Leonardo Da Vinci | leonardo@example.com   |              |
      | Mighty mod        | moderator@example.com  | moderator    |
    When I am logged in as "Mighty mod"
    And I go to the homepage
    Then I click "People"
    Then I should be on "admin/people"
    Then I fill in "Name or email contains" with "Leonardo"
    And I press the "Filter" button
    Then I click "Leonardo Da Vinci"
    Then I click "Edit"
    Then the following fields should be present "Email address, Username, Password, Confirm password"
    Then the following fields should be present "First name, Family name, Photo, Professional domain, Professional profile"
    Then the following fields should be present "Nationality, Professional domain, Organisation"
    Then the following fields should not be present "Time zone"
    And I fill in "First name" with "Leo"
    And I fill in "Family name" with "di ser Piero da Vinci"
    And I press the "Save" button
    Then I should see the success message "The changes have been saved."
    # This message is typical shown when the mail server is not responding. This is just a smoke test
    # to see that all is fine and dandy, and mails are being delivered.
    Then I should not see the error message "Unable to send email. Contact the site administrator if the problem persists."


