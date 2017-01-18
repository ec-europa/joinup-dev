<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;

/**
 * Provides a base class for group (collection, solution) source plugins.
 */
abstract class GroupBase extends JoinupSqlBase {

  use MappingTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('Collection name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return Database::getConnection()
      ->select('joinup_migrate_collection', 'j', ['fetch' => \PDO::FETCH_ASSOC])
      ->fields('j', ['collection']);
  }

}
