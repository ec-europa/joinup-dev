<?php

namespace Drupal\joinup\Context;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\DriverException;

/**
 * Provides step definitions for taking screenshots.
 *
 * Inspired on ScreenShotTrait from https://github.com/nuvoleweb/drupal-behat.
 *
 * @see https://github.com/nuvoleweb/drupal-behat
 */
class ScreenshotContext extends RawMinkContext {

  /**
   * Saves a screen-shot under a given name.
   *
   * @param string $name
   *   The file name.
   *
   * @Then (I )take a screenshot :name
   */
  public function takeScreenshot($name = NULL) {
    $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
    $message = "Screenshot created in @file_name";
    $this->createScreenshot($file_name, $message, FALSE);
  }

  /**
   * Saves a screen-shot under a predefined name.
   *
   * @Then (I )take a screenshot
   */
  public function takeScreenshotUnnamed() {
    $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat-screenshot';
    $message = "Screenshot created in @file_name";
    $this->createScreenshot($file_name, $message);
  }

  /**
   * Make sure there is no PHP notice on the screen during tests.
   *
   * @param \Behat\Behat\Hook\Scope\AfterStepScope $event
   *   The event.
   *
   * @AfterStep
   */
  public function screenshotForPhpNotices(AfterStepScope $event) {
    $environment = $event->getEnvironment();
    // Make sure the environment has the MessageContext.
    $class = 'Drupal\DrupalExtension\Context\MessageContext';
    if ($environment instanceof InitializedContextEnvironment && $environment->hasContextClass($class)) {
      /** @var \Drupal\DrupalExtension\Context\MessageContext $context */
      $context = $environment->getContext($class);
      // Only check if the session is started.
      try {
        if ($context->getMink()->isSessionStarted()) {
          try {
            $context->assertNotWarningMessage('Notice:');
          }
          catch (\Exception $e) {
            // Use the step test in the filename.
            $step = $event->getStep();
            $file_name = str_replace(' ', '_', $step->getKeyword() . '_' . $step->getText());
            $file_name = preg_replace('![^0-9A-Za-z_.-]!', '', $file_name);
            $file_name = substr($file_name, 0, 30);
            $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat-notice__' . $file_name;

            $message = "PHP notice detected, screenshot taken: @file_name";
            $this->createScreenshot($file_name, $message);
            // We don't throw $e any more because we don't fail on the notice.
          }
        }
      }
      catch (DriverException $driver_exception) {

      }
    }
  }

  /**
   * Takes a screen-shot after failed steps (image or html).
   *
   * @param \Behat\Behat\Hook\Scope\AfterStepScope $event
   *   The event.
   *
   * @AfterStep
   */
  public function takeScreenshotAfterFailedStep(AfterStepScope $event) {
    if ($event->getTestResult()->isPassed()) {
      // Not a failed step.
      return;
    }
    $step = $event->getStep();
    $file_name = str_replace(' ', '_', $step->getKeyword() . '_' . $step->getText());
    $file_name = preg_replace('![^0-9A-Za-z_.-]!', '', $file_name);
    $file_name = substr($file_name, 0, 30);
    $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat-failed__' . $file_name;
    $message = "Screenshot for failed step created in @file_name";
    $this->createScreenshot($file_name, $message);
  }

  /**
   * Create a screenshot or save the html.
   *
   * @param string $file_name
   *   The filename of the screenshot (complete).
   * @param string $message
   *   The message to be printed. @file_name will be replaced with $file name.
   * @param bool|true $ext
   *   Whether to add .png or .html to the name of the screenshot.
   */
  public function createScreenshot($file_name, $message, $ext = TRUE) {
    if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
      if ($ext) {
        $file_name .= '.png';
      }
      $screenshot = $this->getSession()->getDriver()->getScreenshot();
      file_put_contents($file_name, $screenshot);
    }
    else {
      if ($ext) {
        $file_name .= '.html';
      }
      $html_data = $this->getSession()->getPage()->getContent();
      file_put_contents($file_name, $html_data);
    }
    if ($message) {
      print strtr($message, ['@file_name' => $file_name]);
    }
  }

}
