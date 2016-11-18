<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Provides a base class for SqlBase classes.
 */
abstract class JoinupSqlBase extends SqlBase {

  /**
   * Collect here table aliases.
   *
   * @var string[]
   */
  protected $alias = [];

  /**
   * Source database name.
   *
   * @var string
   */
  protected $sourceDbName;

  /**
   * Destination database name.
   *
   * @var string
   */

  protected $destinationDbName;

  /**
   * Gets source database name.
   *
   * @return string
   *   The database name.
   */
  public function getSourceDbName() {
    if (!isset($this->sourceDbName)) {
      $this->sourceDbName = Database::getConnection('default', 'migrate')
        ->getConnectionOptions()['database'];
    }
    return $this->sourceDbName;
  }

  /**
   * Gets destination database name.
   *
   * @return string
   *   The database name.
   */
  public function getDestinationDbName() {
    if (!isset($this->destinationDbName)) {
      $this->destinationDbName = Database::getConnection()
        ->getConnectionOptions()['database'];
    }
    return $this->destinationDbName;
  }

}
