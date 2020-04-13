<?php

declare(strict_types = 1);

namespace DrupalProject\PhpUnit;

/**
 * Discovers tests for the Joinup unit test suite.
 */
class JoinupUnitTestSuite extends JoinupTestSuiteBase {

  /**
   * Factory method which loads up a suite with all unit tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $suite = new static('unit');
    $suite->addTestsBySuiteNamespace(NULL, 'Unit');
    return $suite;
  }

}
