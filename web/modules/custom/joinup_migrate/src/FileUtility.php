<?php

namespace Drupal\joinup_migrate;

use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateException;

/**
 * File utility methods.
 */
class FileUtility {

  /**
   * Gets the legacy site files directory.
   *
   * @return string
   *   The legacy site files directory
   *
   * @throws \Drupal\migrate\MigrateException
   *   When the site files directory was not configured.
   */
  public static function getLegacySiteFiles() {
    $files_dir = Settings::get('joinup_migrate.source.files');
    $files_dir = rtrim($files_dir, '/');

    if (empty($files_dir)) {
      throw new MigrateException("Setting 'joinup_migrate.source.files' must be configured in settings.php");
    }

    return $files_dir;
  }

}
