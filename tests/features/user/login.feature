@api
Feature: User login
  In order to perform more actions
  As a user of the website
  I need to be able to login with my credentials.

  Scenario: Users can choose to keep their session open when they login.
    Given users:
      | Username       | Password | E-mail                     |
      | Garnett Tyrell | tyrellg  | garnett.tyrell@example.com |

    # Login without keeping the session open.
    When I go to the homepage
    And I click "Log in"
    And I fill in "Username" with "Garnett Tyrell"
    And I fill in "Password" with "tyrellg"
    And I press "Log in"
    Then I should see the heading "Garnett Tyrell"
    When I close and reopen the browser
    And I go to the homepage
    Then I should see the link "Log in"

    # Login keeping the session open.
    When I click "Log in"
    And I fill in "Username" with "Garnett Tyrell"
    And I fill in "Password" with "tyrellg"
    And I check the box "Remember me"
    And I press "Log in"
    Then I should see the heading "Garnett Tyrell"
    When I close and reopen the browser
    And I go to the homepage
    Then I should not see the link "Log in"
    But I should see the link "My account"

