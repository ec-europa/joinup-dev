<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use Boedah\Robo\Task\Drush\loadTasks;
use Consolidation\AnnotatedCommand\CommandData;
use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Robo\Collection\CollectionBuilder;
use Robo\Exception\AbortTasksException;

/**
 * Provides commands for Virtuoso backend.
 */
class DrushCommands extends AbstractCommands {

  use loadTasks;

  /**
   * Install one or more modules.
   *
   * @param string[] $modules
   *   List of modules to install separated by space.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The Robo collection builder.
   *
   * @command drush:module-install
   */
  public function installModule(array $modules): CollectionBuilder {
    $config = $this->getConfig();
    $task = $this->taskDrushStack($config->get('drush.bin'))
      ->drush('pm:enable ' . implode(',', $modules));
    return $this->collectionBuilder()->addTask($task);
  }

  /**
   * Validates the drush:module-install command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data object.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   When no module has been passed.
   *
   * @hook validate drush:module-install
   */
  public function validateInstallModule(CommandData $commandData): void {
    if (!$commandData->arguments()['modules']) {
      throw new AbortTasksException('At least one module should be specified as command argument.');
    }
  }

}
