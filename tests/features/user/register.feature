@api
Feature: User registration
  As a user I must be able to register to the site and complete my user profile
  and receive appropriate notifications.

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
    And the SuperUser user account is active
