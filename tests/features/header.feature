@api
Feature: Header
  In order to allow users to quickly get to important sections of the site
  As a UX designer
  I want to place links that are relevant to the user's status in the header

  @joinup
  Scenario Outline: Header shows basic info and directs anonymous users to log in or create an account
    Given I am not logged in
    And I am on <page>
    Then I should see the Joinup logo in the navigation bar
    And I should see the text "Interoperability solutions" in the "Navigation bar"
    And I should see the link "About us" in the "Navigation bar"
    And I should see the link "Sign in" in the "Navigation bar"
    When I click "About us" in the "Navigation bar"
    Then I should see the heading "About Joinup"

    Given I am on <page>
    When I click "Sign in" in the "Navigation bar"
    Then I should see the heading "Sign in to continue"

    Examples:
      | page         |
      | the homepage |
