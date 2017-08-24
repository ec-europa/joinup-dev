@api @javascript
Feature:
  As a user of the website
  When I try to login
  I want to be redirected to the page where I came from.

  Background:
    Given user:
      | Username    | Mr Redirect             |
      | Password    | Mr Redirect             |
      | First name  | Mr                      |
      | Family name | Redirect                |
      | E-mail      | Mr.redirect@example.com |

  Scenario: A user logging in from the front page should be redirected to his profile.
    When I go to the homepage
    And I click "Sign in"
    And I fill in "Username" with "Mr Redirect"
    And I fill in "Password" with "Mr Redirect"
    And I press "Sign in"
    Then I should see the heading "Mr Redirect"
    And the url should match "/user/\d+"

  Scenario: A user logging in from another page should return to that page after login.
    When I am on "/search?keys=how+to+redirect"
    And I open the account menu
    And I click "Sign in"
    And I fill in "Username" with "Mr Redirect"
    And I fill in "Password" with "Mr Redirect"
    And I press "Sign in"
    Then I should not see the heading "Mr Redirect"
    And the relative url should be "/search?keys=how+to+redirect"
