<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
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
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = Database::getConnection()
      ->select('joinup_migrate_collection', 'c', ['fetch' => \PDO::FETCH_ASSOC])
      ->fields('c', ['contact'])
      ->isNotNull('c.contact');

    if ($collection) {
      $query->condition('c.collection', $collection);
    }

    $contacts = [];
    foreach ($query->execute()->fetchCol() as $item) {
      $item = substr(substr($item, 1), 0, strlen($item) - 2);
      $contacts = array_merge($contacts, array_map('intval', explode('|', $item)));
    }

    return array_values(array_unique($contacts));
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
