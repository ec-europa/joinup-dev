@api @email
Feature: User registration
  As a user I must be able to register to the site and complete my user profile
  and receive appropriate notifications.

  Scenario: User can find the register page
    Given I am an anonymous user
    When I am on the homepage
    And I click "Sign in"
    Then I am at "/user/login"
    When I click "Create new account"
    Then I am at "/user/register"

  Scenario: User can register with minimal required fields
    Given all e-mails have been sent
    And the following user registration at "/user/register":
      | E-mail      | Test-User@Example.com |
      | Username    | TeStUSer              |
      | First name  | Test                  |
      | Family name | User                  |
    Then I should see the success message "Thank you for applying for an account. Your account is currently pending approval by the site administrator."
    And the following system email should have been sent:
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
    Then the following system email should have been sent:
      | recipient | TeStUSer                                                                                                                        |
      | subject   | Your Joinup account has been activated.                                                                                         |
      | body      | Your account at Joinup has been activated. You may now log in by clicking this link or copying and pasting it into your browser |
    And the account for TeStUSer should be active

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
      | E-mail                     | superuser@example.org |
      | Username                   | SuperUser             |
      | First name                 | Super                 |
      | Family name                | User                  |
      | Password                   | SuperSecret           |
      | Confirm password           | SuperSecret           |
      | Notify user of new account | 1                     |
      | Active                     | 1                     |
    Then the following system email should have been sent:
      | recipient | SuperUser                                                                                                                                                                   |
      | subject   | Your Joinup account was created successfully.                                                                                                                               |
      | body      | Your account on Joinup has been created.  If you believe that a Joinup account linked to this email address was erroneously created, please contact The Joinup Support Team |
    And the account for SuperUser should be active
