<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;

/**
 * Provides common methods to deal with collection and solution contact info.
 */
trait ContactTrait {

  /**
   * Gets collection contact info.
   *
   * @param string|null $collection
   *   (optional) If passed, the results will be limited to this collection.
   *
   * @return int[]
   *   A list of source publisher node IDs.
   */
  protected function getCollectionContacts($collection = NULL) {
    $or = (new Condition('OR'))
      ->isNotNull('r.vid')
      ->isNotNull('s.vid');

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->getMappingBaseQuery()
      ->condition('j.type', ['asset_release', 'repository'], 'IN')
      ->condition('j.owner', 'Yes')
      ->condition('n.status', 1)
      ->condition($or);

    if ($collection) {
      $query->condition('j.collection', $collection);
    }

    $query->leftJoin(JoinupSqlBase::getSourceDbName() . '.node', 'n', 'j.nid = n.nid');
    $query->leftJoin(JoinupSqlBase::getSourceDbName() . '.content_type_asset_release', 's', 'n.vid = s.vid');
    $query->leftJoin(JoinupSqlBase::getSourceDbName() . '.content_type_repository', 'r', 'n.vid = r.vid');

    // The NID is provided either by repository or by solution.
    $query->addExpression("IFNULL(r.field_repository_contact_point_nid, s.field_asset_contact_point_nid)", 'allowed_nid');

    return array_values(array_filter(array_unique($query->execute()->fetchCol())));
  }

  /**
   * Gets solution contact info.
   *
   * @param int|null $solution_vid
   *   (optional) If passed, the result will be limited to this node.
   *
   * @return int[]
   *   A list of source publisher node IDs.
   */
  protected function getSolutionContacts($solution_vid = NULL) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->getMappingBaseQuery()
      ->condition('j.type', 'asset_release')
      ->condition('n.status', 1)
      ->isNotNull('s.vid');

    if ($solution_vid) {
      $query->condition('s.vid', $solution_vid);
    }

    $query->leftJoin(JoinupSqlBase::getSourceDbName() . '.node', 'n', 'j.nid = n.nid');
    $query->leftJoin(JoinupSqlBase::getSourceDbName() . '.content_type_asset_release', 's', 'n.vid = s.vid');

    $query->addExpression('s.field_asset_contact_point_nid', 'allowed_nid');

    return array_values(array_filter(array_unique($query->execute()->fetchCol())));
  }

}
