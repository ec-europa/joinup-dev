<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Migrates solutions.
 *
 * @MigrateSource(
 *   id = "solution"
 * )
 */
class Solution extends GroupBase  {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
      'uri' => $this->t('URI'),
      'title' => $this->t('Title'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['node'] = $query->leftJoin("{$this->getSourceDbName()}.node", 'n', "j.nid = %alias.nid");
    $this->alias['uri'] = $query->leftJoin("{$this->getSourceDbName()}.content_field_id_uri", 'uri', "{$this->alias['node']}.vid = %alias.vid");

    $query
      ->fields($this->alias['node'], ['nid', 'title'])
      ->condition("{$this->alias['node']}.type", 'asset_release');

    return $query;
  }

}
