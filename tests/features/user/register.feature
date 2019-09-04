@api
Feature: User registration
  As a user I must be able to register to the site and complete my user profile
  and receive appropriate notifications.

  Scenario: User can find the register page
    Given I am an anonymous user
    When I am on the homepage
    And I click "Sign in (legacy)"
    Then I should see the heading "Sign in"
    When I click "Create new account"
    Then I should see the heading "Create new account"

  @email
  Scenario: User can register with minimal required fields
    Given all e-mails have been sent
    And the following user registration at "/user/register":
      | Email       | Test-User@Example.com |
      | Username    | TeStUSer              |
      | First name  | Test                  |
      | Family name | User                  |
    Then I should see the success message "Thank you for applying for an account. Your account is currently pending approval by the site administrator."
    And the following email should have been sent:
      | recipient | TeStUSer                                                                                                                                                                                                                                         |
      | subject   | Your Joinup account is pending approval.                                                                                                                                                                                                         |
      | body      | Thank you for registering at Joinup. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to sign in, set your password, and other details. |
    And the account for TeStUSer should be blocked
    Given I am logged in as a moderator
    And I am on the homepage
    When I click "People"
    And I click "Edit" in the "Test User" row
    And I select the radio button "Active"
    And I press "Save"
    Then the following email should have been sent:
      | recipient | TeStUSer                                                                                                                        |
      | subject   | Your Joinup account has been activated.                                                                                         |
      | body      | Your account at Joinup has been activated. You may now log in by clicking this link or copying and pasting it into your browser |
    And the account for TeStUSer should be active

  @email
  Scenario: A moderator can register a user
    Given users:
      | Username      | Roles     |
      | Mr. Moderator | Moderator |
    And all e-mails have been sent
    And I am logged in as "Mr. Moderator"
    When I am on the homepage
    Then I click "People"
    Then I click "Add user"
    Then I am at "admin/people/create"
    Given the following user registration at "admin/people/create":
      | Email                      | superuser@example.org |
      | Username                   | SuperUser             |
      | First name                 | Super                 |
      | Family name                | User                  |
      | Password                   | 98eZRhoP              |
      | Confirm password           | 98eZRhoP              |
      | Notify user of new account | 1                     |
      | Active                     | 1                     |
    Then the following email should have been sent:
      | recipient | SuperUser                                                                                                                       |
      | subject   | Your Joinup account was created successfully.                                                                                   |
      | body      | The Joinup Support Team created your account on Joinup. Please log in through the following link in order to set your password. |
    And the account for SuperUser should be active

  @email
  Scenario: A user account whose first and last names are identical is
    deleted after creation but the user is receiving all notification as if the
    account would have been created.

    Given I am an anonymous user
    And I am on the homepage
    When I click "Sign in (legacy)"
    Then I click "Create new account"

    Given I fill in "Email" with "spam@example.com"
    And I fill in "Username" with "spam"
    And I fill in "First name" with "Spam"
    And I fill in "Family name" with "Spam"
    And I wait for the spam protection time limit to pass
    Given I press "Create new account"
    Then I should see the success message "Thank you for applying for an account. Your account is currently pending approval by the site administrator."
    And the following email should have been sent:
      | recipient_mail | spam@example.com                                                                                                                                                                                                                                 |
      | subject        | Your Joinup account is pending approval.                                                                                                                                                                                                         |
      | body           | Thank you for registering at Joinup. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to sign in, set your password, and other details. |
    Then I should not have a spam user

  @honeypot
  Scenario: Spammers get delayed when trying to register accounts
    When I am at "/user/register"
    And I fill in "Email" with "egg-and-bacon@example.com"
    And I fill in "Username" with "egg-and-spam"
    And I fill in "First name" with "Egg Bacon And Spam"
    And I fill in "Family name" with "Spam Bacon Sausage And Spam"
    And I press "Create new account"
    Then I should see the error message "There was a problem with your form submission. Please wait 6 seconds and try again."
    When I press "Create new account"
    Then I should see the error message "There was a problem with your form submission. Please wait 11 seconds and try again."
    When I press "Create new account"
    Then I should see the error message "There was a problem with your form submission. Please wait 24 seconds and try again."
