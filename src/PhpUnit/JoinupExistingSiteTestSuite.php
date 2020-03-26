<?php

declare(strict_types = 1);

namespace DrupalProject\PhpUnit;

/**
 * Discovers tests for the Joinup existing site test suite.
 */
class JoinupExistingSiteTestSuite extends JoinupTestSuiteBase {

  /**
   * Factory method which loads up a suite with all existing site tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $suite = new static('existing-site');
    $suite->addTestsBySuiteNamespace(NULL, 'ExistingSite');
    return $suite;
  }

}
