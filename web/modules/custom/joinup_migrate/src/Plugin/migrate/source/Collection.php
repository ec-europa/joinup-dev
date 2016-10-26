<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates collections.
 *
 * @MigrateSource(
 *   id = "collection"
 * )
 */
class Collection extends CollectionBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'new_collection' => $this->t('New collection?'),
      'policy' => $this->t('Policy domain'),
      'pre_moderation' => $this->t('Pre moderation'),
      'collection_state' => $this->t('Collection state'),
      'field_ar_state' => $this->t('State'),
      'abstract' => $this->t('Abstract'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['og'] = $query->leftJoin("{$this->dbName}.og", 'og', "{$this->alias['node']}.nid = %alias.nid");

    $query
      ->fields('j', [
        'collection',
        'new_collection',
        'policy',
        'pre_moderation',
        'collection_state',
        'abstract',
      ])
      ->fields($this->alias['node'], ['nid', 'type'])
      ->fields($this->alias['og'], ['og_description']);

    $query->addExpression("{$this->alias['uri']}.field_id_uri_value", 'uri');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$abstract = $row->getSourceProperty('abstract')) {
      $row->setSourceProperty('abstract', $row->getSourceProperty('og_description'));
    }

    return parent::prepareRow($row);
  }

}
