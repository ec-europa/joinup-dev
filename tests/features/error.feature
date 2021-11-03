@wip @api @errorPage @group-c
Feature: On errors I want to see a friendly page and be able to report an
  incident number/code.

  Scenario: Test exception, fatal error, user error.

    # We're not using a scenario outline as that would be too expensive because
    # it would run the before/after hooks on each example.

    # Error level: hide.
    Given the site error reporting verbosity is "hide"

    When I am on "/error_page_test/exception"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And I should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should not see "Exception: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->exception()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/fatal_error"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should not see "Error: Call to undefined method Drupal\error_page_test\Controller\ErrorPageTestController::functionDoesNotExist() in Drupal\error_page_test\Controller\ErrorPageTestController->fatalError()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/user_error"
    Then the response status code should be 200
    And I should not see the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should not see "User error: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->userError()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/php_notice"
    Then the response status code should be 200
    And I should not see the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should not see "Notice: Undefined variable: not_initialised_variable in Drupal\error_page_test\Controller\ErrorPageTestController->notice()"
    And the response should not contain "<pre class=\"backtrace\">"

    # Error level: some.
    Given the site error reporting verbosity is "some"

    When I am on "/error_page_test/exception"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And I should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should see "Exception: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->exception()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/fatal_error"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should see "Error: Call to undefined method Drupal\error_page_test\Controller\ErrorPageTestController::functionDoesNotExist() in Drupal\error_page_test\Controller\ErrorPageTestController->fatalError()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/user_error"
    Then the response status code should be 200
    And I should see the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should see "User error: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->userError()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/php_notice"
    Then the response status code should be 200
    And I should not see the following success messages:
      | success messages                                                 |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should not see "Notice: Undefined variable: not_initialised_variable in Drupal\error_page_test\Controller\ErrorPageTestController->notice()"
    And the response should not contain "<pre class=\"backtrace\">"

    # Error level: all.
    Given the site error reporting verbosity is "all"

    When I am on "/error_page_test/exception"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And I should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should see "Exception: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->exception()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/fatal_error"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should see "Error: Call to undefined method Drupal\error_page_test\Controller\ErrorPageTestController::functionDoesNotExist() in Drupal\error_page_test\Controller\ErrorPageTestController->fatalError()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/user_error"
    Then the response status code should be 200
    And I should see the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should see "User error: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->userError()"
    And the response should not contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/php_notice"
    Then the response status code should be 200
    And I should see the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should see "Notice: Undefined variable: not_initialised_variable in Drupal\error_page_test\Controller\ErrorPageTestController->notice()"
    And the response should not contain "<pre class=\"backtrace\">"

    # Error level: verbose.
    Given the site error reporting verbosity is "verbose"

    When I am on "/error_page_test/exception"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And I should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should see "Exception: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->exception()"
    And the response should contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/fatal_error"
    Then the response status code should be 500
    And I should see the heading "There was an unexpected problem serving your request"
    And should see text matching "Please try again and contact us if the problem persist including [0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12} in your message."
    And I should see "Error: Call to undefined method Drupal\error_page_test\Controller\ErrorPageTestController::functionDoesNotExist() in Drupal\error_page_test\Controller\ErrorPageTestController->fatalError()"
    And the response should contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/user_error"
    Then the response status code should be 200
    And I should see the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should see "User error: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->userError()"
    And the response should contain "<pre class=\"backtrace\">"

    When I am on "/error_page_test/php_notice"
    Then the response status code should be 200
    And I should see the following error messages:
      | error messages                                                   |
      | There was an unexpected problem serving your request.            |
      | Please try again and contact us if the problem persist including |
      | in your message.                                                 |
    And I should see "Notice: Undefined variable: not_initialised_variable in Drupal\error_page_test\Controller\ErrorPageTestController->notice()"
    And the response should contain "<pre class=\"backtrace\">"
