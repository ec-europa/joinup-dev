<?php

declare(strict_types = 1);

namespace Joinup\Composer;

use Composer\Script\Event;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates required files.
 */
class ScriptHandler {

  /**
   * Creates required files.
   */
  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    // Create the files directory with chmod 0755.
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0755);
      umask($oldmask);
      $event->getIO()->write("Created a sites/default/files directory with chmod 0755");
    }
  }

}
