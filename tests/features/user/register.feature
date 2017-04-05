@api
Feature: User registration
  As a user I must be able to register to the site and complete my user profile.

  # @todo The login text should be changed - "Sign in" instead of "Log in" - https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2639.
  Scenario: User can find the register page
    Given I am an anonymous user
    When I am on the homepage
    And I click "Log in"
    Then I am at "/user/login"
    When I click "Create new account"
    Then I am at "/user/register"

  Scenario: User can register with minimal required fields
    Given the following user registration at "/user/register":
      | Email address      | Test-User@Example.com |
      | Username           | TeStUSer              |
      | First name         | Test                  |
      | Family name        | User                  |
    Then I should see the success message "A welcome message with further instructions has been sent to your email address."

  Scenario: A moderator can register a user
    Given users:
      | Username        | Roles     |
      | Mr. Moderator   | Moderator |
    And I am logged in as "Mr. Moderator"
    When I am on the homepage
    Then I click "People"
    Then I click "Add user"
    Then I am at "admin/people/create"
    Given the following user registration at "admin/people/create":
      | Email address              | superuser@example.org |
      | Username                   | SuperUser             |
      | First name                 | Super                 |
      | Family name                | User                  |
      | Password                   | SuperSecret           |
      | Confirm password           | SuperSecret           |
      | Notify user of new account | 1                     |
