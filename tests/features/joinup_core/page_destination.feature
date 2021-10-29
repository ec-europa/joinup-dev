@api @casMockServer @group-c
Feature:
  As a user of the website
  When I try to login
  I want to be redirected to the page where I came from.

  Background:
    Given user:
      | Username    | Mr Redirect             |
      | First name  | Mr                      |
      | Family name | Redirect                |
      | E-mail      | mr.redirect@example.com |
    And CAS users:
      | Username   | E-mail                  | Password    | First name | Last name | Local username |
      | mrredirect | mr.redirect@example.com | Mr Redirect | Mister     | Redirect  | Mr Redirect    |

  Scenario: A user logging in from the front page should end up again on the homepage.
    When I go to the homepage
    And I click "Sign in" in the "Navigation bar"
    And I fill in "E-mail address" with "mr.redirect@example.com"
    And I fill in "Password" with "Mr Redirect"
    And I press "Log in"
    Then I should be on the homepage

  Scenario: A user logging in from another page should return to that page after login.
    When I am on "/search?keys=how+to+redirect"
    And I click "Sign in with EU Login"
    And I fill in "E-mail address" with "mr.redirect@example.com"
    And I fill in "Password" with "Mr Redirect"
    And I press "Log in"
    Then I should not see the heading "Mr Redirect"
    And the relative url should be "/search?keys=how%20to%20redirect"

  Scenario: Only the destination parameter should be carried over if one exists outside the user pages.
    When I am on "/search?keys=how+to+redirect&destination=/contact"
    And I click "Sign in with EU Login"
    And I fill in "E-mail address" with "mr.redirect@example.com"
    And I fill in "Password" with "Mr Redirect"
    And I press "Log in"
    Then I should not see the heading "Mr Redirect"
    And the relative url should be "/contact"
