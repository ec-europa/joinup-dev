@api @errorPage
Feature: On errors I want to see a friendly page and be able to report an
  incident number/code.

  Scenario: Test exception, fatal error, user error.
    When I am on "/error_page_test/exception"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."

    When I am on "/error_page_test/fatal_error"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."

    When I am on "/error_page_test/user_error"
    Then the response status code should be 200
    And I should see the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
