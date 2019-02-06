@api @errorPage
Feature: On errors I want to see a friendly page and be able to report an
  incident number/code.

  Scenario Outline: Test exception, fatal error, user error.
    Given the site error reporting verbosity is "<error level>"

    When I am on "/error_page_test/exception"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And I should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should <see backtrace> "Exception: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->exception()"

    When I am on "/error_page_test/fatal_error"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should <see backtrace> "Error: Call to undefined method Drupal\error_page_test\Controller\ErrorPageTestController::functionDoesNotExist() in Drupal\error_page_test\Controller\ErrorPageTestController->fatalError()"

    When I am on "/error_page_test/user_error"
    Then the response status code should be 200
    And I should <see user error message> the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should <see backtrace> "User error: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->userError()"

    Examples:
      | error level | see backtrace | see user error message |
      | hide        | not see       | not see                |
      | some        | not see       | see                    |
      | all         | not see       | see                    |
      | verbose     | see           | see                    |
