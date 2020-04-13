<?php

declare(strict_types = 1);

namespace DrupalProject\PhpUnit;

/**
 * Discovers tests for the Joinup functional javascript test suite.
 */
class JoinupFunctionalJavascriptTestSuite extends JoinupTestSuiteBase {

  /**
   * Factory method which loads up a suite with all functional javascript tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $suite = new static('functional-javascript');
    $suite->addTestsBySuiteNamespace(NULL, 'FunctionalJavascript');
    return $suite;
  }

}
