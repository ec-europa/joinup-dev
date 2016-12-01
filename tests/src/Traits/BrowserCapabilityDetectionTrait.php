<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Exception\UnsupportedDriverActionException;

/**
 * Helper methods for detecting browser capabilities.
 */
trait BrowserCapabilityDetectionTrait {

  /**
   * Checks whether the browser supports JavaScript.
   *
   * @return bool
   *   Returns TRUE when the browser environment supports executing JavaScript
   *   code, for example because the test is running in Selenium or PhantomJS.
   */
  public function browserSupportsJavascript() {
    $driver = $this->getSession()->getDriver();
    try {
      $driver->executeScript('return;');
      return TRUE;
    }
    catch (UnsupportedDriverActionException $e) {
      return FALSE;
    }
  }

}
