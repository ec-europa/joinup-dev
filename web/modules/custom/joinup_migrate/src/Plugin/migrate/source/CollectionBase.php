<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;

/**
 * Base class for collection migrations.
 */
abstract class CollectionBase extends GroupBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'collection' => ['type' => 'string'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['node'] = $query->leftJoin('node', 'n', 'j.nid = %alias.nid');
    $this->alias['community'] = $query->leftJoin('content_type_community', 'comm', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['repository'] = $query->leftJoin('content_type_repository', 'repo', "{$this->alias['node']}.vid = %alias.vid");

    $or = (new Condition('OR'))
      ->condition('j.nid', 0)
      ->condition("{$this->alias['node']}.status", 1);

    return $query->condition($or);
  }

}
