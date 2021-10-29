<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Joinup\Traits\DigitQaPipelineAwareTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Context to run within DIGIT QA GitLab pipeline.
 */
class DigitQaPipelineContext extends RawMinkContext {

  use DigitQaPipelineAwareTrait;

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
      $artifactsPath = static::getArtifactsPath($event->getSuite()->getName());
      if (!$fileSystem->exists($artifactsPath)) {
        $fileSystem->mkdir($artifactsPath);
      }
      $fileSystem->chgrp($artifactsPath, getenv('DAEMON_GROUP'), TRUE);
      $fileSystem->chmod($artifactsPath, 0775);
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
