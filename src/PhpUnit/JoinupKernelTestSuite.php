<?php

declare(strict_types = 1);

namespace DrupalProject\PhpUnit;

/**
 * Discovers tests for the Joinup kernel test suite.
 */
class JoinupKernelTestSuite extends JoinupTestSuiteBase {

  /**
   * Factory method which loads up a suite with all kernel tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $suite = new static('kernel');
    $suite->addTestsBySuiteNamespace(NULL, 'Kernel');
    return $suite;
  }

}
