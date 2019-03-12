<?php

declare(strict_types = 1);

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
  protected function browserSupportsJavaScript(): bool {
    $driver = $this->getSession()->getDriver();
    try {
      $driver->executeScript('return;');
      return TRUE;
    }
    catch (UnsupportedDriverActionException $e) {
      return FALSE;
    }
  }

  /**
   * Checks that we are running on a JavaScript-enabled browser.
   *
   * @throws \LogicException
   *   Thrown when not running on a JS-enabled browser.
   */
  protected function assertJavaScriptEnabledBrowser(): void {
    if (!$this->browserSupportsJavaScript()) {
      // Show a helpful error message.
      throw new \LogicException('This test needs to run on a real browser using Selenium or similar. Please add the "@javascript" tag to the scenario.');
    }
  }

}
