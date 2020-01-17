@api
Feature: User login
  In order to perform more actions
  As a user of the website
  I need to be able to login with my credentials.

  # Todo: needs to be adapted to use EU Login in ISAICP-5760.
  @wip
  Scenario: Users can choose to keep their session open when they login.
    Given users:
      | Username       | Password | E-mail                     |
      | Garnett Tyrell | tyrellg  | garnett.tyrell@example.com |

    # Login using an e-mail for username.
    When I am not logged in
    And I go to the homepage
    And I click "Sign in (legacy)"
    Then I should not see the text "Log in"
    And I fill in "Email or username" with "garnett.tyrell@example.com"
    And I fill in "Password" with "tyrellg"
    And I press "Sign in"
    Then I should see the heading "Garnett Tyrell"
