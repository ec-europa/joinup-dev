<?php

namespace DrupalProject\composer;

use Composer\EventDispatcher\Event;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Fixes a bug in 'cweagans/composer-patches' that creates unneeded files.
 *
 * While patching packages with 'cweagans/composer-patches', some erroneous
 * directories and files are created. This is a known issue in the plugin,
 * filled at https://github.com/cweagans/composer-patches/issues/60. While such
 * files are created as copies of other legit files, in some edge cases, they
 * could interfere and confuse the Drupal discovery mechanism. In this Composer
 * post-update and post-install script we simply remove those directories. This
 * script will be removed when a fix will be provided upstream. The unneeded
 * directories are typically created as:
 * - web/core/b/
 * - web/core/core/
 *
 * @see https://github.com/cweagans/composer-patches/issues/60
 *
 * @todo Remove this hack when cweagans/composer-patches/issues/60 is fixed.
 */
class RemoveWrongPatchedObjects {

  /**
   * Removes files and directories left after creating a patch.
   *
   * @param \Composer\EventDispatcher\Event $event
   *   The Composer event.
   */
  public static function remove(Event $event): void {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $coreDir = $drupalFinder->getDrupalRoot() . '/core';

    $subDirs = [
      'b',
      'core',
    ];

    foreach ($subDirs as $subDir) {
      $dir = "$coreDir/$subDir";
      if ($fs->exists($dir)) {
        $fs->remove($dir);
      }
    }
  }

}
