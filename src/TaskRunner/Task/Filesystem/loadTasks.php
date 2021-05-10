<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Task\Filesystem;

trait loadTasks
{
    /**
     * @param string|string[] $files
     *
     * @return \Joinup\TaskRunner\Task\Filesystem\DeleteFile|\Robo\Collection\CollectionBuilder
     */
    protected function taskDeleteFile($files)
    {
        return $this->task(DeleteFile::class, $files);
    }

}
