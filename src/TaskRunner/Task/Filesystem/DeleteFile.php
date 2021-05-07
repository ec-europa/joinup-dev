<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Task\Filesystem;

use Robo\Common\ResourceExistenceChecker;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Filesystem\Filesystem as sfFilesystem;

/**
 * Deletes files.
 *
 * ``` php
 * <?php
 * $this->taskDeleteFile('tmp/output.log')->run();
 * ?>
 * ```
 */
class DeleteFile extends BaseTask {

  use ResourceExistenceChecker;

  /**
   * The paths to the files that need to be deleted.
   *
   * @var string[]
   */
  protected $files = [];

  /**
   * The Symfony Filesystem component.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * Constructs a DeleteFile task.
   *
   * @param string|string[] $files
   *   The files to delete.
   */
  public function __construct($files) {
    is_array($files) ? $this->files = $files : $this->files[] = $files;

    $this->fs = new sfFilesystem();
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (!$this->checkResources($this->files, 'file')) {
      return Result::error($this, 'Source files are missing!');
    }

    foreach ($this->files as $file) {
      $this->fs->remove($file);
      $this->printTaskInfo("Deleted {file}...", ['file' => $file]);
    }

    return Result::success($this);
  }

}
