<?php

namespace Drupal\joinup_migrate\Plugin;

use Drupal\Core\Database\Database;

/**
 * Provides some common methods for migration plugins.
 */
trait JoinupMigrateTrait {

  /**
   * Gets the Drupal 6 database name.
   *
   * @return string
   */
  protected function getSourceDatabaseName() {
    return Database::getConnectionInfo('migrate')['default']['database'];
  }

}
