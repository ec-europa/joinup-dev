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
  protected static $sourceDbName;

  /**
   * Destination database name.
   *
   * @var string
   */

  protected static $destinationDbName;

  /**
   * {@inheritdoc}
   */
  protected function prepareQuery() {
    $this->query = parent::prepareQuery();
    // Save the alias list.
    $this->query->addMetaData('alias', $this->alias);
    return $this->query;
  }

  /**
   * Gets source database name.
   *
   * @return string
   *   The database name.
   */
  public static function getSourceDbName() {
    if (!isset(static::$sourceDbName)) {
      static::$sourceDbName = Database::getConnection('default', 'migrate')
        ->getConnectionOptions()['database'];
    }
    return static::$sourceDbName;
  }

  /**
   * Gets destination database name.
   *
   * @return string
   *   The database name.
   */
  public static function getDestinationDbName() {
    if (!isset(static::$destinationDbName)) {
      static::$destinationDbName = Database::getConnection()
        ->getConnectionOptions()['database'];
    }
    return static::$destinationDbName;
  }

  /**
   * Gets the legacy site webroot directory.
   *
   * @return string
   *   The legacy site webroot directory
   *
   * @throws \Drupal\migrate\MigrateException
   *   When the webroot was not configured.
   */
  protected function getLegacySiteWebRoot() {
    $webroot = Settings::get('joinup_migrate.source.root');

    if (empty($webroot)) {
      throw new MigrateException('The web root of the D6 site is not configured. Please run `phing setup-migration`.');
    }

    return rtrim($webroot, '/');
  }

}
