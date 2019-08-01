@api @casMockServer
Feature: Log in through EU Login
  In order to access all website of the European Commission with the same credentials
  As a user with an existing EU Login account
  I need to be able to register and log in to Joinup using EU Login

  Scenario: A new user logging in through EU Login should be approved by a moderator
    Given CAS users:
      | Username    | E-mail                         | Password  | First name | Last name | Domain            |
      | chucknorris | texasranger@chucknorris.com.eu | Qwerty098 | Chuck      | Norris    | eu.europa.europol |

    Given I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    # The user gets redirected to the mock server.
    Then I should see the heading "Sign in to continue"
    When I fill in "E-mail address" with "texasranger@chucknorris.com.eu"
    And I fill in "Password" with "wrong password"
    And I press the "Log in" button
    Then I should see the error message "Unrecognized user name or password."

    When I fill in "Password" with "Qwerty098"
    And I press the "Log in" button
    # The user gets redirected back to Drupal.

    # Blocking users registered via EU Login is still to be decided after a
    # final resolution on ISAICP-5333. The tendency is to register users as
    # active. Until a decision is made consider such users as active users and
    # we temporary comment out this step.
    # Then I should see "Thank you for applying for an account. Your account is currently pending approval by the site administrator."
    Then I click "Sign out"
    And the user chucknorris should have the following data in their user profile:
      | First name            | Chuck                  |
      | Family name           | Norris                 |
      | EU login organisation | European Police Office |

    # Upon second log in the user should be informed that the account is not yet
    # activated.
    When I click "Sign in"
    And I click "EU Login"
    Then I should see the heading "Sign in to continue"
    When I fill in "E-mail address" with "texasranger@chucknorris.com.eu"
    When I fill in "Password" with "Qwerty098"
    And I press the "Log in" button

    # Blocking users registered via EU Login is still to be decided after a
    # final resolution on ISAICP-5333. The tendency is to register users as
    # active. Until a decision is made consider such users as active users and
    # we temporary comment out this step.
    # Then I should see "Your account is blocked or has not been activated. Please contact a site administrator."

  Scenario: An existing user can log in through EU Login
    Given users:
      | Username | E-mail     |
      | jb007    | 007@mi6.eu |
    Given CAS users:
      | Username | E-mail     | Password           | First name | Last name |
      | jb007    | 007@mi6.eu | shaken_not_stirred | James      | Bond      |

    Given I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    Then I should see the heading "Sign in to continue"
    When I fill in "E-mail address" with "007@mi6.eu"
    When I fill in "Password" with "shaken_not_stirred"
    And I press the "Log in" button
    # This will be completed in ISAICP-5337
