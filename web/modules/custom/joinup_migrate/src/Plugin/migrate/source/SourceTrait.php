<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;

/**
 * Provides common methods for source plugins.
 */
trait SourceTrait {

  /**
   * Gets source database name.
   *
   * @return string
   *   The database name.
   */
  public function getSourceDbName() {
    return $this->getDatabase()->getConnectionOptions()['database'];
  }

  /**
   * Gets destination database name.
   *
   * @return string
   *   The database name.
   */
  public function getDestinationDbName() {
    return Database::getConnection()->getConnectionOptions()['database'];
  }

}
