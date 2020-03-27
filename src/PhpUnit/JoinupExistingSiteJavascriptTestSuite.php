<?php

declare(strict_types = 1);

namespace DrupalProject\PhpUnit;

/**
 * Discovers tests for the Joinup existing site javascript test suite.
 */
class JoinupExistingSiteJavascriptTestSuite extends JoinupTestSuiteBase {

  /**
   * Factory method which loads a suite with all existing site javascript tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $suite = new static('existing-site-javascript');
    $suite->addTestsBySuiteNamespace(NULL, 'ExistingSiteJavascript');
    return $suite;
  }

}
