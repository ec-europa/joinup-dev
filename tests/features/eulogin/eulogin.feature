Feature: As anonymous user, in order to register or login to Joinup, I want to
  be able to use my EU Login account.

  Scenario: Test that a link to EU Login exists on the login page.
    Given I am an anonymous user
    When I visit "/user/login"
    Then I should see the link "EU Login"
