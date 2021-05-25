<?php

declare(strict_types = 1);

namespace Joinup\Composer;

use Composer\EventDispatcher\Event;
use GitWrapper\GitWrapper;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Stores the current git tag in a file.
 *
 * We are showing the current release in the footer of the website. This will
 * capture the release from the git tag whenever the project is installed or
 * updated using Composer, and stores it in a file for later retrieval.
 */
class CaptureGitReleaseTag {

  /**
   * Stores the project version based on the current git tag in a file.
   *
   * @param \Composer\EventDispatcher\Event $event
   *   The Composer event.
   */
  public static function capture(Event $event): void {
    $directory = getcwd();

    $wrapper = new GitWrapper();
    $git = $wrapper->workingCopy($directory);

    $count = $git->run(['rev-list HEAD --count']);
    $event->getIO()->write("Commit count: $count");

    $remotes = $git->getRemotes();
    $event->getIO()->write("Remotes:");
    foreach ($remotes as $remote => $urls) {
      $event->getIO()->write("- $remote:");
      foreach ($urls as $action => $url) {
        $event->getIO()->write("  - $action: $url");
      }
    }

    $event->getIO()->write("Deepening tree and fetching tags...");
    $git->fetch(['deepen' => 5000, 'tags' => TRUE]);

    $count = $git->run(['rev-list HEAD --count']);
    $event->getIO()->write("Commit count: $count");

    $version = trim((string) $git->run(['describe --tags']));

    $fs = new Filesystem();
    $fs->dumpFile("$directory/VERSION", $version);

    $event->getIO()->write("$version written to $directory/VERSION.");
  }

}
