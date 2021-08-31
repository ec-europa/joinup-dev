<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use OpenEuropa\TaskRunner\Contract\FilesystemAwareInterface;
use OpenEuropa\TaskRunner\Tasks\CollectionFactory\loadTasks as CollectionFactoryTasks;
use OpenEuropa\TaskRunner\Tasks\ProcessConfigFile\loadTasks as ProcessConfigFileTasks;
use OpenEuropa\TaskRunner\Traits\FilesystemAwareTrait;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputOption;

/**
 * Provides commands to run tests.
 */
class TestsCommands extends AbstractCommands implements FilesystemAwareInterface{

  use CollectionFactoryTasks;
  use FilesystemAwareTrait;
  use ProcessConfigFileTasks;

  /**
   * Replaces the toolkit:test-behat original command.
   *
   * This is a slightly changed fork of original TestsCommands::toolkitBehat().
   * The main difference is that additional commands could run before and/or
   * after the Behat tests. Such commands should be described in configuration
   * files in this way:
   * @code
   * behat:
   *   commands:
   *     before:
   *       - task: exec
   *         command: ls -la
   *       - ...
   *     after:
   *       - task: exec
   *         command: whoami
   *       - ...
   * @endcode
   *
   * @param array $options
   *   The command line options.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The Robo collection builder.
   *
   * @hook replace-command toolkit:test-behat
   *
   * @see \EcEuropa\Toolkit\TaskRunner\Commands\TestsCommands::toolkitBehat()
   */
  public function toolkitBehat(array $options = [
    'from' => InputOption::VALUE_OPTIONAL,
    'to' => InputOption::VALUE_OPTIONAL,
    'suite' => 'default'
  ]): CollectionBuilder {
    $tasks = [];

    // Execute a list of commands to run before tests.
    if ($commands = $this->getConfig()->get('behat.commands.before')) {
      $tasks[] = $this->taskCollectionFactory($commands);
    }

    $this->taskProcessConfigFile($options['from'], $options['to'])->run();

    $behat_bin = $this->getConfig()->get('runner.bin_dir') . '/behat';
    $tasks[] = $this->taskExec("{$behat_bin} --suite={$options['suite']}");

    // Execute a list of commands to run after tests.
    if ($commands = $this->getConfig()->get('behat.commands.after')) {
      $tasks[] = $this->taskCollectionFactory($commands);
    }

    return $this->collectionBuilder()->addTaskList($tasks);
  }

}
