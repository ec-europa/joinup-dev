<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

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
   * If the query has been already prepared.
   *
   * @var bool
   */
  protected $isQueryPrepared = FALSE;

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

}
