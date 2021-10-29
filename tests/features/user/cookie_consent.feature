@api  @group-e
Feature: Cookie consent
  In order to be compliant with the ePrivacy directive
  As a product owner
  I want to offer the possibility for users to reject the use of cookies

  @javascript
  Scenario: Accept or refuse cookies
    Given I am an anonymous user
    And I am on homepage
    Then I should see the text "This site uses cookies to offer you a better browsing experience. Find out more on how we use cookies and how you can change your settings." in the "Cookie consent banner"
    And I should see the link "I accept cookies" in the "Cookie consent banner"
    And I should see the link "I refuse cookies" in the "Cookie consent banner"

    Given I am logged in as a user with the "authenticated" role
    And I am on homepage
    Then I should see the text "This site uses cookies to offer you a better browsing experience. Find out more on how we use cookies and how you can change your settings." in the "Cookie consent banner"
    And I should see the link "I accept cookies" in the "Cookie consent banner"
    And I should see the link "I refuse cookies" in the "Cookie consent banner"
