<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Robo\Collection\CollectionBuilder;
use Robo\Task\Composer\loadTasks;

/**
 * Provides Composer commands.
 */
class ComposerCommands extends AbstractCommands {

  use loadTasks;

  /**
   * Options to be used with most of Composer commands.
   *
   * @var array
   */
  const DEFAULT_OPTIONS = [
    'prefer-source' => FALSE,
    'prefer-dist' => FALSE,
    'dev' => TRUE,
    'optimize-autoloader' => FALSE,
    'ignore-platform-reqs' => FALSE,
    'no-plugins' => FALSE,
    'no-scripts' => FALSE,
  ];

  /**
   * Install one or more modules.
   *
   * @param array $options
   *   The command line options.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The Robo collection builder.
   *
   * @command composer:install
   */
  public function install(array $options = self::DEFAULT_OPTIONS): CollectionBuilder {
    $task = $this->taskComposerInstall($this->getConfig()->get('composer.bin'));
    $this->applyOptions($task, $options);
    return $this->collectionBuilder()->addTask($task);
  }

  /**
   * Applies Composer options.
   *
   * @param \Robo\Collection\CollectionBuilder $task
   *   The Composer Robo task.
   * @param array $options
   *   The command line options.
   */
  protected function applyOptions(CollectionBuilder $task, array $options): void {
    /** @var \Robo\Task\Composer\Base $task */
    if ($options['prefer-source']) {
      $task->preferSource();
    }

    $task
      ->preferDist($options['prefer-dist'])
      ->dev($options['dev'])
      ->optimizeAutoloader($options['optimize-autoloader'])
      ->disablePlugins($options['no-plugins'])
      ->noScripts($options['no-scripts']);

    if ($options['ignore-platform-reqs']) {
      $task->ignorePlatformRequirements($options['ignore-platform-reqs']);
    }
  }

}
