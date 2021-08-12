<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Traits;

use DrupalFinder\DrupalFinder;
use OpenEuropa\TaskRunner\TaskRunner;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Reusable methods to allow Task Runner usage from code.
 */
trait TaskRunnerTrait {

  /**
   * Runs an OpenEuropa Task Runner command.
   *
   * @param string $command
   *   The task runner command.
   */
  protected static function runCommand(string $command): void {
    $initialDir = getcwd();
    $input = new StringInput("{$command} --working-dir=" . static::getPath());
    $runner = new TaskRunner($input, new NullOutput(), static::getClassLoader());
    $runner->run();
    chdir($initialDir);
  }

  /**
   * Returns the class loader.
   *
   * @return \Composer\Autoload\ClassLoader
   *   The class loader.
   */
  protected static function getClassLoader() {
    return require static::getPath('vendor/autoload.php');
  }

  /**
   * Returns a full-qualified path to a project directory or file.
   *
   * @param string|null $subDir
   *   (optional) A subdirectory relative to the project root. If omitted, the
   *   project root directory is returned.
   *
   * @return string
   *   A path to a project's directory or file.
   */
  protected static function getPath(?string $subDir = NULL): string {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $path = $drupalFinder->getComposerRoot();
    if ($subDir) {
      $path .= DIRECTORY_SEPARATOR . $subDir;
    }
    return $path;
  }

}
