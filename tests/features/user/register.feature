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
      | Email address | Test-User@Example.com |
      | Username      | TeStUSer              |
      | First name    | Test                  |
      | Family name   | User                  |
    Then I should see the success message "A welcome message with further instructions has been sent to your email address."
    And the following system email should have been sent:
      | recipient | TeStUSer                                                                                                                                                                               |
      | subject   | You're one step away to create your Joinup account. Please confirm your email address before you can sign in to Joinup.                                                                 |
      | body      | You are one step away from creating your account in Joinup, the European Commission collaborative platform for Interoperability solutions for public administrations, businesses and citizens. |

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
      | Email address              | superuser@example.org |
      | Username                   | SuperUser             |
      | First name                 | Super                 |
      | Family name                | User                  |
      | Password                   | SuperSecret           |
      | Confirm password           | SuperSecret           |
      | Notify user of new account | 1                     |
    And the following system email should have been sent:
      | recipient | SuperUser                                                                                                                     |
      | subject   | Your Joinup account was created successfully.                                                                                 |
      | body      | To quickly familiarise yourself with the functionalities available to registered users, you can follow a short tour of Joinup |
