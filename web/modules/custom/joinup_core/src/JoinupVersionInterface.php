<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Url;

/**
 * Interface for services that retrieve the current Joinup version.
 *
 * The version is saved during building the codebase in a `VERSION.txt` file, in
 * the project's webroot directory.
 */
interface JoinupVersionInterface {

  /**
   * The path to the file that contains the Joinup version.
   */
  const PATH = DRUPAL_ROOT . '/VERSION.txt';

  /**
   * Returns a string that uniquely identifies the current Joinup version.
   *
   * @return string
   *   The Joinup version. This is in the format as returned by `git describe`.
   *   - If a release is checked out this will be the git tag, e.g. 'v1.56.0'.
   *   - If a development version is checked out, the version is composed of the
   *     closest git tag, followed by a dash, the number of commits that follow
   *     the tag, another dash, a letter identifying the version control system
   *     (in our case the letter 'g' for 'git'), followed by the git SHA of the
   *     latest commit. Example: 'v1.56.1-775-geada0ce61'.
   *   - The string 'n/a' if the file containing the version doesn't exist.
   *
   * @see https://git-scm.com/docs/git-describe#_examples
   */
  public function getVersion(): string;

  /**
   * Returns the URL of the current Joinup release or code snapshot.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  public function getUrl(): Url;

}
