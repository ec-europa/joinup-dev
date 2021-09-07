<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Provides step definitions for taking screenshots.
 *
 * Inspired on ScreenShotTrait from https://github.com/nuvoleweb/drupal-behat.
 *
 * @see https://github.com/nuvoleweb/drupal-behat
 */
class ScreenshotContext extends RawMinkContext {

  /**
   * Optional directory where the screenshots are saved.
   *
   * @var string
   */
  protected $localDir;

  /**
   * Optional folder on an S3 bucket where screenshots will be uploaded to.
   *
   * @var string
   */
  protected $s3Dir;

  /**
   * Optional AWS region where the Amazon S3 bucket is located.
   *
   * @var string
   */
  protected $s3Region;

  /**
   * Optional name of the Amazon S3 bucket where screenshots will be uploaded.
   *
   * @var string
   */
  protected $s3Bucket;

  /**
   * Constructs a new ScreenshotContext context.
   *
   * @param string|null $localDir
   *   Optional directory where the screenshots are saved. If omitted the
   *   screenshots will not be saved.
   * @param string|null $s3Dir
   *   Optional folder on an Amazon S3 bucket where screenshots will be uploaded
   *   to. If omitted, the screenshots will not be uploaded to AWS S3.
   * @param string|null $s3Region
   *   Optional AWS region where the Amazon S3 bucket is located. If omitted,
   *   the screenshots will not be uploaded to AWS S3.
   * @param string|null $s3Bucket
   *   Optional name of the Amazon S3 bucket where screenshots will be uploaded.
   *   If omitted, the screenshots will not be uploaded to AWS S3.
   *
   * @see tests/behat.yml.dist
   */
  public function __construct(?string $localDir = NULL, ?string $s3Dir = NULL, ?string $s3Region = NULL, ?string $s3Bucket = NULL) {
    $this->localDir = $localDir;
    $this->s3Dir = $s3Dir;
    $this->s3Region = $s3Region;
    $this->s3Bucket = $s3Bucket;
  }

  /**
   * Saves a screenshot under a given name.
   *
   * @param string|null $name
   *   The file name.
   *
   * @Then I take a screenshot :name
   */
  public function takeScreenshot(?string $name = NULL): void {
    $message = "Screenshot: @file_name";
    $this->createScreenshot($name, $message);
  }

  /**
   * Saves a screenshot under a predefined name.
   *
   * @Then I take a screenshot
   */
  public function takeScreenshotUnnamed(): void {
    $file_name = 'screenshot-' . user_password();
    $message = "Screenshot: @file_name";
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
  public function screenshotForPhpNotices(AfterStepScope $event): void {
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
          catch (ExpectationException $e) {
            $message = "{$this->getStepLine($event)} screenshot: @file_name";
            $this->createScreenshot($this->buildScreenshotFileName('php-notice', $event), $message, $event->getSuite()->getName());
            // We don't throw $e anymore because we don't fail on the notice.
          }
        }
      }
      catch (DriverException $driver_exception) {

      }
    }
  }

  /**
   * Takes a screenshot after failed steps (image or html).
   *
   * @param \Behat\Behat\Hook\Scope\AfterStepScope $event
   *   The event.
   *
   * @AfterStep
   */
  public function takeScreenshotAfterFailedStep(AfterStepScope $event): void {
    if (!$event->getTestResult()->isPassed()) {
      $message = "{$this->getStepLine($event)} screenshot: @file_name";
      $this->createScreenshot($this->buildScreenshotFileName('failed', $event), $message, $event->getSuite()->getName());
    }
  }

  /**
   * Creates a screenshot in HTML or PNG format.
   *
   * @param string $file_name
   *   The filename of the screenshot (complete).
   * @param string $message
   *   The message to be printed. '@file_name' will be replaced with $file_name.
   * @param string|null $suite_name
   *   (optional) Suite name. Passed only from hook callers.
   */
  public function createScreenshot(string $file_name, string $message, ?string $suite_name = NULL): void {
    try {
      if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
        $file_name .= '.png';
        $screenshot = $this->getSession()->getDriver()->getScreenshot();
      }
      else {
        $file_name .= '.html';
        $screenshot = $this->getSession()->getPage()->getContent();
      }
    }
    catch (DriverException $e) {
      // A DriverException might occur if no page has been loaded yet so no
      // screenshot can yet be taken. In this case we exit silently, allowing
      // the remainder of the test suite to run.
      return;
    }

    // Save the screenshot locally.
    $path = $this->save($screenshot, $file_name, $suite_name);

    // Upload the screenshot to Amazon S3.
    $this->upload($screenshot, $file_name);

    if ($message) {
      print strtr($message, ['@file_name' => $path ?: $file_name]);
      // Depending on the output formatter used, Behat will suppress any output
      // generated during the test. Flush the output buffers so out message will
      // show up in the test logs.
      ob_flush();
    }
  }

  /**
   * Saves the given screenshot to the local filesystem.
   *
   * @param string $screenshot
   *   The screenshot data.
   * @param string $file_name
   *   The file name.
   * @param string|null $suite_name
   *   (optional) If passed, will be used to build the screenshot file path.
   *
   * @return string|null
   *   The saved screenshot path.
   *
   * @throws \Exception
   *   Thrown if the destination folder doesn't exist and couldn't be created.
   */
  protected function save(string $screenshot, string $file_name, ?string $suite_name = NULL): ?string {
    // Don't attempt to save the screenshot if no folder name has been
    // configured.
    if (empty($this->localDir)) {
      return NULL;
    }

    // Ensure the directory exists.
    $dir = rtrim($this->localDir, DIRECTORY_SEPARATOR);
    if ($suite_name) {
      $dir .= DIRECTORY_SEPARATOR . $suite_name;
    }

    if (!is_dir($dir)) {
      if (!mkdir($dir, 0755, TRUE)) {
        throw new \Exception("The '$dir' folder does not exist and could not be created.");
      }
    }
    $path = $dir . DIRECTORY_SEPARATOR . $file_name;
    file_put_contents($path, $screenshot);

    return $path;
  }

  /**
   * Uploads the given screenshot to Amazon S3.
   *
   * @param string $screenshot
   *   The screenshot data.
   * @param string $file_name
   *   The file name.
   *
   * @throws \Exception
   *   Thrown if the destination folder doesn't exist and couldn't be created.
   */
  protected function upload(string $screenshot, string $file_name): void {
    // Don't attempt to upload the screenshot if any of the required parameters
    // are missing.
    $required_parameters = ['s3Dir', 's3Region', 's3Bucket'];
    foreach ($required_parameters as $required_parameter) {
      if (empty($this->$required_parameter)) {
        return;
      }
    }

    $client = $this->getS3Client();
    // Prepend the UNIX timestamp to the filename to add some degree of
    // uniqueness to the filename, because S3 doesn't allow to overwrite
    // existing files.
    $file_name = (string) time() . '-' . $file_name;
    $path = $this->s3Dir . '/' . $file_name;
    $client->upload($this->s3Bucket, $path, $screenshot);
  }

  /**
   * Returns an instance of the Amazon S3 client.
   *
   * @return \Aws\S3\S3ClientInterface
   *   The client.
   */
  protected function getS3Client(): S3ClientInterface {
    return new S3Client([
      'version' => 'latest',
      'region' => $this->s3Region,
    ]);
  }

  /**
   * Builds a filename from the feature path and failed step line.
   *
   * @param string $type
   *   The type of event (failure, PHP notice, etc).
   * @param \Behat\Behat\Hook\Scope\AfterStepScope $event
   *   The after step event.
   *
   * @return string
   *   The screenshot filename.
   */
  protected function buildScreenshotFileName(string $type, AfterStepScope $event): string {
    $step_line = str_replace('/', '--', ltrim(substr($event->getFeature()->getFile(), strlen($event->getEnvironment()->getSuite()->getSetting('paths')[0])), '/'));
    return "{$type}--{$step_line}--{$event->getStep()->getLine()}";
  }

  /**
   * Returns a concatenation of the feature file name and the failed step line.
   *
   * @param \Behat\Behat\Hook\Scope\AfterStepScope $event
   *   The after step event.
   *
   * @return string
   *   A concatenation of the feature file name and the failed step line.
   */
  protected function getStepLine(AfterStepScope $event): string {
    // Remove the common path for readability reasons.
    return ltrim(substr($event->getFeature()->getFile(), strlen($event->getEnvironment()->getSuite()->getSetting('paths')[0])), '/') . ":{$event->getStep()->getLine()}";
  }

}
