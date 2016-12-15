<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;

/**
 * Provides base methods for mapping table queries.
 */
trait MappingTrait {

  /**
   * Gets a base query for mapping table.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Base query for mapping table.
   */
  protected function getMappingBaseQuery() {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = Database::getConnection()->select('joinup_migrate_mapping', 'j', ['fetch' => \PDO::FETCH_ASSOC])
      ->condition('j.del', 'No')
      ->condition('j.collection', ['', '#N/A'], 'NOT IN')
      ->condition('n.status', 1);

    $query->leftJoin(JoinupSqlBase::getSourceDbName() . '.node', 'n', 'j.nid = n.nid');

    return $query;
  }

}
