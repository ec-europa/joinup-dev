@api @casMockServer
Feature: Login through OE Authentication
  In order to be able to access the CMS backend
  As user of the system
  I need to login through OE Authentication
  I need to be redirect back to the site

  Scenario: Login/Logout with eCAS mockup server of internal users
    Given CAS users:
      | Username    | E-mail                            | Password           | First name | Last name |
      | chucknorris | texasranger@chucknorris.com.eu    | Qwerty098          | Chuck      | Norris    |
      | jb007       | 007@mi6.eu                        | shaken_not_stirred | James      | Bond      |
      | lissa       | Lisbeth.SALANDER@ext.ec.europa.eu | dragon_tattoo      | Lisbeth    | Salander  |

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
    Then I should see "You have been logged in."
    And I should see the link "My account"
    And I should see the link "Sign out"
    And I should not see the link "Sign in"

    When I click "My account"
    Then I should see the heading "chucknorris"

    # Profile contains extra fields.
    When I click "Edit"
    Then the "First Name" field should contain "Chuck"
    And the "Last Name" field should contain "NORRIS"
    And the "Department" field should contain "DIGIT.A.3.001"
    And the "Organisation" field should contain "eu.europa.ec"

    When I click "Log out"
    # Redirected to the Ecas mockup server.
    And I press the "Log me out" button
    # Redirected back to Drupal.
    Then I should be on the homepage
    And I should not see the link "My account"
    And I should not see the link "Sign out"
    And I should see the link "Sign in"
