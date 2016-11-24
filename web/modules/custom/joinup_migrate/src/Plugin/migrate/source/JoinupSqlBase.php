<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateException;
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
  protected function getSourceDbName() {
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
  protected function getDestinationDbName() {
    if (!isset($this->destinationDbName)) {
      $this->destinationDbName = Database::getConnection()
        ->getConnectionOptions()['database'];
    }
    return $this->destinationDbName;
  }

  /**
   * Gets the legacy site webroot directory.
   *
   * @return string
   *   The legacy site webroot directory
   *
   * @throws \Drupal\migrate\MigrateException
   *   The the webroot was not configured.
   */
  protected function getLegacySiteWebRoot() {
    $webroot = Settings::get('joinup_migrate.source.root');

    if (empty($webroot)) {
      throw new MigrateException('The web root of the D6 site is not configured. Please run `phing setup-migration`.');
    }

    return rtrim($webroot, '/');
  }

}
