<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Task\Npm;

trait loadTasks
{
    /**
     * @param null|string $pathToNpm
     *
     * @return \Joinup\TaskRunner\Task\Npm\Run|\Robo\Collection\CollectionBuilder
     */
    protected function taskNpmRun($pathToNpm = null)
    {
        return $this->task(Run::class, $pathToNpm);
    }

}
