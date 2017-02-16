<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

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
   * If the query has been already prepared.
   *
   * @var bool
   */
  protected $isQueryPrepared = FALSE;

  /**
   * A list of source objects that should be checked for existing URIs.
   *
   * Migration source plugin classes should implement this property to declare
   * a list of tables/views that should be checked for the 'uri' field in order
   * to build an 'already taken' URI list. For example 'solution' migration
   * might want to set this property as:
   * @code
   * protected $reservedUriTables = ['collection'];
   * @endcode
   * In this way, the migration will prohibit using URIs that are already
   * present in the field 'uri' of the source table 'd8_collection'. TL;DR:
   * Solutions cannot have URIs (as IDs) that are already in Collections.
   *
   * Note that array items should not have the 'd8_' prefix.
   *
   * @var string[]
   */
  protected $reservedUriTables = [];

  /**
   * {@inheritdoc}
   */
  protected function prepareQuery() {
    if (!$this->isQueryPrepared) {
      $this->query = parent::prepareQuery();
      // Save the alias list.
      $this->query->addMetaData('alias', $this->alias);
      $this->isQueryPrepared = TRUE;
    }
    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    // @see https://www.drupal.org/node/2833060
    $query = clone $this->prepareQuery();
    return $query->countQuery()->execute()->fetchField();
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

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($this->reservedUriTables && $row->hasSourceProperty('uri') && ($uri = $row->getSourceProperty('uri'))) {
      $reserved = $this->getUrisToExclude();
      if (in_array($uri, $reserved)) {
        // This URI is in the reserved list. Generate a new one.
        $row->setSourceProperty('uri', NULL);
      }
    }
    return parent::prepareRow($row);
  }

  /**
   * Builds a list of URIs to be forbidden in the current migration.
   *
   * @return string[]
   *   A list of URIs.
   */
  protected function getUrisToExclude() {
    static $cache = [];

    $uri = [];

    foreach ($this->reservedUriTables as $table) {
      $table = "d8_$table";
      if (!isset($cache[$table])) {
        if ($this->getDatabase()->schema()->tableExists($table) && $this->getDatabase()->schema()->fieldExists($table, 'uri')) {
          $cache[$table] = $this->select($table)
            ->fields($table, ['uri'])
            ->isNotNull('uri')
            ->execute()
            ->fetchCol();
        }
      }
      $uri = array_merge($uri, array_diff($cache[$table], $uri));
    }

    return $uri;
  }

}
