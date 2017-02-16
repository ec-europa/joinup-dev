<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

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
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('d8_prepare', 'c');
    $query
      ->fields('c', ['publisher'])
      ->isNotNull('c.publisher');

    if ($collection) {
      $query->condition('c.collection', $collection);
    }

    $owners = [];
    foreach ($query->execute()->fetchCol() as $item) {
      $item = substr(substr($item, 1), 0, strlen($item) - 2);
      $owners = array_merge($owners, array_map('intval', explode('|', $item)));
    }

    return array_values(array_unique($owners));
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
    $query = $this->getMappingBaseQuery();
    $query
      ->condition('j.type', 'asset_release')
      ->condition('n.status', 1)
      ->isNotNull('s.vid')
      ->condition('g.type', 'repository');

    if ($solution_vid) {
      $query->condition('s.vid', $solution_vid);
    }

    $query->join('node', 'n', 'j.nid = n.nid');
    $query->join('content_field_asset_publisher', 's', 'n.vid = s.vid');
    $query->join('og_ancestry', 'oa', 'j.nid = oa.nid');
    $query->join('node', 'g', 'oa.group_nid = g.nid');

    $query->addExpression('s.field_asset_publisher_nid', 'allowed_nid');

    return array_values(array_filter(array_unique($query->execute()->fetchCol())));
  }

}
