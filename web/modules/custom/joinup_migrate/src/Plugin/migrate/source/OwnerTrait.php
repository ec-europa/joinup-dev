<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;

/**
 * Provides common methods to deal with collection and solution owners.
 */
trait OwnerTrait {

  /**
   * Gets collection owners.
   *
   * @param string|null $collection
   *   (optional) If passed, the results will be limited to this collection.
   *
   * @return int[]
   *   A list of source publisher node IDs.
   */
  protected function getCollectionOwners($collection = NULL) {
    $or = (new Condition('OR'))
      ->isNotNull('r.vid')
      ->isNotNull('s.vid');

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->getGroupOwnersBaseQuery()
      ->condition('j.type', ['asset_release', 'repository'], 'IN')
      ->condition('j.owner', 'Yes')
      ->condition($or);

    if ($collection) {
      $query->condition('j.collection', $collection);
    }

    $query->leftJoin(static::getSourceDbName() . '.content_field_repository_publisher', 'r', 'n.vid = r.vid');

    // The NID is provided either by repository or by solution.
    $query->addExpression("IFNULL(r.field_repository_publisher_nid, s.field_asset_publisher_nid)", 'allowed_nid');

    return array_values(array_filter(array_unique($query->execute()->fetchCol())));
  }

  /**
   * Gets solution owners.
   *
   * @param int|null $solution_vid
   *   (optional) If passed, the result will be limited to this node.
   *
   * @return int[]
   *   A list of source publisher node IDs.
   */
  protected function getSolutionOwners($solution_vid = NULL) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->getGroupOwnersBaseQuery()
      ->condition('j.type', 'asset_release')
      ->isNotNull('s.vid');

    if ($solution_vid) {
      $query->condition('s.vid', $solution_vid);
    }

    $query->addExpression('s.field_asset_publisher_nid', 'allowed_nid');

    return array_values(array_filter(array_unique($query->execute()->fetchCol())));
  }

  /**
   * Returns a base query to be used by collection and solution queries.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Base query to be used by collection and solution queries.
   */
  protected function getGroupOwnersBaseQuery() {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = Database::getConnection()->select('joinup_migrate_mapping', 'j')
      ->condition('j.del', 'No')
      ->condition('j.collection', ['', '#N/A'], 'NOT IN')
      ->condition('n.status', 1);

    $query->leftJoin(static::getSourceDbName() . '.node', 'n', 'j.nid = n.nid');
    $query->leftJoin(static::getSourceDbName() . '.content_field_asset_publisher', 's', 'n.vid = s.vid');

    return $query;
  }

}
