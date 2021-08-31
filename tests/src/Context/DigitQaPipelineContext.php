<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Context to run within DIGIT QA GitLab pipeline.
 */
class DigitQaPipelineContext extends RawMinkContext {

  /**
   * Initializes the environment before running the tests.
   *
   * @param \Behat\Testwork\Hook\Scope\BeforeSuiteScope $event
   *   The before suite scope event.
   *
   * @BeforeSuite
   */
  public static function init(BeforeSuiteScope $event): void {
    if (static::isDigitQaPipeline()) {
      $fileSystem = new Filesystem();
      $paths = [
        // The public and private files directory.
        implode(DIRECTORY_SEPARATOR, [
          getenv('CI_PROJECT_DIR'),
          'web',
          'sites',
          'default',
          'files',
        ]),
        // The artifacts directory.
        static::getArtifactsPath($event->getSuite()->getName()),
      ];

      foreach ($paths as $path) {
        if (!$fileSystem->exists($path)) {
          $fileSystem->mkdir($path);
        }
        $fileSystem->chgrp($path, getenv('DAEMON_GROUP'), TRUE);
        $fileSystem->chmod($path, 0775);
      }
    }
  }

  /**
   * Saves the relevant logs as artifacts in case of failure.
   *
   * @param \Behat\Testwork\Hook\Scope\AfterSuiteScope $event
   *   The after suite scope event.
   *
   * @AfterSuite
   */
  public static function saveLogsAsArtifacts(AfterSuiteScope $event): void {
    if (static::isDigitQaPipeline() && !$event->getTestResult()->isPassed()) {
      $projectPath = rtrim(getenv('CI_PROJECT_DIR'), DIRECTORY_SEPARATOR);
      $artifactsPath = static::getArtifactsPath($event->getSuite()->getName());
      exec("{$projectPath}/vendor/bin/drush sql:dump --tables-list=watchdog --gzip --result-file={$artifactsPath}/watchdog.sql --root={$projectPath}");
    }
  }

  /**
   * Checks if we're running inside DIGIT QA GitLab pipeline context.
   *
   * @return bool
   *   TRUE if we're running in DIGIT QA GitLab pipeline.
   */
  protected static function isDigitQaPipeline(): bool {
    // @todo Add more checks.
    return getenv('GITLAB_CI') === 'true' && getenv('TOOLKIT_PROJECT_ID') === 'digit-joinup';
  }

  /**
   * Returns the suite artifacts path.
   *
   * @param string $suiteName
   *   The suite name.
   *
   * @return string
   *   The suite artifacts path.
   */
  protected static function getArtifactsPath(string $suiteName): string {
    return rtrim(getenv('ARTIFACTS_DIR'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $suiteName;
  }

}
