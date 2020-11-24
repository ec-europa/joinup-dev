<?php

declare(strict_types = 1);

namespace Joinup\PhpUnit\Hooks;

use Joinup\TaskRunner\Traits\TaskRunnerTrait;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;

/**
 * Hooks into PHPUnit test suite in order to provide proper Drupal settings.
 */
class DrupalSettingsHook implements BeforeFirstTestHook, AfterLastTestHook {

  use TaskRunnerTrait;

  /**
   * {@inheritdoc}
   */
  public function executeBeforeFirstTest(): void {
    $this->runCommand("drupal:settings phpunit --root={$this->getPath('web')} --sites-subdir=default");
  }

  /**
   * {@inheritdoc}
   */
  public function executeAfterLastTest(): void {
    $this->runCommand("drupal:settings site-clean --root={$this->getPath('web')} --sites-subdir=default");
  }

}
