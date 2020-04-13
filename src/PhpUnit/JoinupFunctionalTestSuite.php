<?php

declare(strict_types = 1);

namespace DrupalProject\PhpUnit;

/**
 * Discovers tests for the Joinup functional test suite.
 */
class JoinupFunctionalTestSuite extends JoinupTestSuiteBase {

  /**
   * Factory method which loads up a suite with all functional tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $suite = new static('functional');
    $suite->addTestsBySuiteNamespace(NULL, 'Functional');
    return $suite;
  }

}
