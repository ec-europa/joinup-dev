<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Url;

/**
 * A service that retrieves the current Joinup version.
 *
 * The version is saved during composer install in a `VERSION` file in the root
 * folder of the project.
 */
class JoinupVersion implements JoinupVersionInterface {

  /**
   * The current Joinup version.
   *
   * @var string
   */
  protected $version;

  /**
   * {@inheritdoc}
   */
  public function getVersion(): string {
    if (empty($this->version)) {
      $filename = DRUPAL_ROOT . '/../VERSION';
      $this->version = file_exists($filename) ? $this->version = trim(file_get_contents($filename)) : 'n/a';

      // Sanitize the version string. This is perhaps overkill since if a hacker
      // can access the version file we are in big trouble anyway. In any case
      // this will ensure that our version string and derived link will never
      // break the page layout.
      $this->version = preg_replace('/[^\w.-]/', '', $this->version);
    }

    return $this->version;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): Url {
    $version = $this->getVersion();

    // If a development version is checked out, return a link to the currently
    // checked out commit.
    if (preg_match('/^.+-\d+-g([a-f0-9]+)$/', $version, $matches) === 1) {
      return Url::fromUri('https://github.com/ec-europa/joinup-dev/commit/' . $matches[1]);
    }

    // If the current version could not be determined, return a link to the
    // releases page on Github.
    if ($version === 'n/a') {
      return Url::fromUri('https://github.com/ec-europa/joinup-dev/releases');
    }

    // If a tag is checked out, return a link to the release that matches the
    // tag.
    return Url::fromUri('https://github.com/ec-europa/joinup-dev/releases/tag/' . $version);
  }

}
