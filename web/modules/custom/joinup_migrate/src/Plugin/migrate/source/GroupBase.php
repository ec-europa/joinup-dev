<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;

/**
 * Provides a base class for group (collection, solution) source plugins.
 */
abstract class GroupBase extends JoinupSqlBase {

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
    $query = Database::getConnection()->select('joinup_migrate_mapping', 'j', ['fetch' => \PDO::FETCH_ASSOC]);

    return $query
      ->fields('j', ['collection'])
      ->orderBy('j.collection')
      ->condition('j.del', 'No')
      ->condition('j.collection', ['', '#N/A'], 'NOT IN');
  }

}
