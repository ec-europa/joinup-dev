@api
Feature: Cookie consent kit
  In order to ensure privacy
  As a user of the website
  I want to be able to decide on the cookie settings.

  @javascript
  Scenario Outline: Accept cookies
    Given user:
      | Username | test_cck |
      | Password | test_cck |
    And CAS users:
      | Username | E-mail               | Password |
      | test_cck | test_cck@example.com | test_cck |

    When I am an anonymous user
    And I am on the homepage
    Then I should see the text "This site uses cookies to offer you a better browsing experience. Find out more on how we use cookies and how you can change your settings." in the "Cookie consent banner"
    And I should see the link "I accept cookies" in the "Cookie consent banner"
    And I should see the link "I refuse cookies" in the "Cookie consent banner"

    When I click "I <link> cookies"
    Then I should not see the "Cookie consent banner" region

    # Logging in does not require re-sign in.
    When I am on the homepage
    And I open the account menu
    And I click "Sign in"
    And I fill in "E-mail address" with "test_cck@example.com"
    And I fill in "Password" with "test_cck"
    And I press "Log in"
    Then I should not see the "Cookie consent banner" region

    Examples:
      | link   |
      | accept |
      | refuse |
