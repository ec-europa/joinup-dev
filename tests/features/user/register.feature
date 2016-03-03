@api
Feature: User registration
  As a user I must be able to register to the site and complete my user profile.

  Scenario: User can find the register page
    Given I am an anonymous user
    When I am on the homepage
    And I click "Log in"
    Then I am at "/user/login"

    When I click "Create new account"
    Then I am at "/user/register"

  @javascript
  Scenario: User can register with minimal required fields
    Given the following user registration:
      | Email address      | Test-User@Example.com |
      | Username           | TeStUSer              |
      | First name         | Test                  |
      | Family name        | User                  |
    Then I should see the success message "A welcome message with further instructions has been sent to your email address."
