<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Context to run within DIGIT QA GitLab pipeline.
 */
class DigitQaPipelineContext extends RawMinkContext {

  /**
   * Initializes the environment before running the tests.
   *
   * @BeforeSuite
   */
  public static function init(): void {
    if (static::isDigitQaPipeline()) {
      $fileSystem = new Filesystem();
      // Allow Apache to write public and private files directories.
      $publicFilesPath = implode(DIRECTORY_SEPARATOR, [
        getenv('CI_PROJECT_DIR'),
        'web',
        'sites',
        'default',
        'files',
      ]);
      $fileSystem->chgrp($publicFilesPath, getenv('DAEMON_GROUP'), TRUE);
      $fileSystem->chmod($publicFilesPath, 0775);
    }

    // @todo These two lines are here for debugging purposes. Will be removed as
    //   soon as we've fixed the tests in pipeline.
    print shell_exec('export');
    print shell_exec('ls -la /var/log/apache2');
    ob_flush();
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
      $artifactsPath = rtrim(getenv('ARTIFACTS_DIR'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $event->getSuite()->getName();
      $fileSystem = new Filesystem();
      $fileSystem->mkdir($artifactsPath);
      exec("{$projectPath}/vendor/bin/drush sql:dump --tables-list=watchdog --gzip --result-file={$artifactsPath}/watchdog.sql --root={$projectPath}");

      // @todo These two lines are here for debugging purposes. Will be removed as
      //   soon as we've fixed the tests in pipeline.
      print shell_exec('export');
      print shell_exec('ls -la /var/log/apache2');
      ob_flush();
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

}
