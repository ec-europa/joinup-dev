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

  @joinup @javascript
  Scenario Outline: The 'Get started' button opens a popup with information about EU Login
    Given I am not logged in
    And I am on <page>

    # The popup is not shown initially.
    Then I should not see the link "Create an account"
    And I should not see the text "As a signed-in user you can create content, become a member of a community, receive notifications on your favourite solutions and topics, and access all other features available on the platform."

    # The popup appears when clicking on 'Get started'.
    When I press "Get started"
    Then I should see the link "Create an account"
    And I should see the text "As a signed-in user you can create content, become a member of a community, receive notifications on your favourite solutions and topics, and access all other features available on the platform."

    # Even though the link claims to lead to an account creation page, we are in
    # fact obliged to authenticate through EU Login. If the user doesn't have an
    # EU Login account yet they can create one inside the EU Login portal. We
    # just have to check that the link leads to our mocked EU Login portal.
    When I click "Create an account"
    Then I should see the heading "Sign in to continue"

    Examples:
      | page         |
      | the homepage |
