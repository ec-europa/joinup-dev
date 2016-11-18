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
  public function fields() {
    return [
      'collection' => $this->t('Collection name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['node'] = $query->leftJoin("{$this->getSourceDbName()}.node", 'n', "j.nid = %alias.nid AND j.new_collection = 'No' AND %alias.type IN ('community', 'repository')");
    $this->alias['uri'] = $query->leftJoin("{$this->getSourceDbName()}.content_field_id_uri", 'uri', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['community'] = $query->leftJoin("{$this->getSourceDbName()}.content_type_community", 'comm', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['repository'] = $query->leftJoin("{$this->getSourceDbName()}.content_type_repository", 'repo', "{$this->alias['node']}.vid = %alias.vid");

    $or = (new Condition('OR'))
      ->condition((new Condition('AND'))
        ->condition('j.new_collection', 'Yes')
        ->isNotNull('j.policy')
        ->isNotNull('j.abstract')
      )
      ->condition("{$this->alias['node']}.type", ['community', 'repository'], 'IN');

    return $query->condition($or);
  }

}
