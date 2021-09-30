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

    # The links "Home" and "About us" are only shown in the hamburger menu since
    # people using mobile devices are always on the move and need to navigate as
    # quickly as possible.
    And I should see the following items in the main navigation:
      | link        | desktop menu | hamburger menu |
      | Home        | not shown    | link           |
      | Sign in     | link         | link           |
      | Get started | button       | not shown      |
      | About us    | not shown    | link           |

    # Check that the links lead to the expected pages.
    When I click "Home" in the "Hamburger menu"
    Then I should be on "/"

    When I click "About us" in the "Hamburger menu"
    Then I should see the heading "About Joinup"

    Given I am on <page>
    When I click "Sign in" in the "Navigation bar"
    Then I should see the heading "Sign in to continue"

    Given I am on <page>
    When I click "Sign in" in the "Hamburger menu"
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
