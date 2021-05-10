<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Task\Npm;

use Robo\Contract\CommandInterface;
use Robo\Task\Npm\Base;

/**
 * Task that executes the `npm run` command.
 *
 * ``` php
 * <?php
 * $this->taskNpmRun('watch')->run();
 * ?>
 * ```
 */
class Run extends Base implements CommandInterface
{
    /**
     * {@inheritdoc}
     */
    protected $action = 'run';

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->printTaskInfo('Run Npm script: {arguments}', ['arguments' => $this->arguments]);
        return $this->executeCommand($this->getCommand());
    }
}
