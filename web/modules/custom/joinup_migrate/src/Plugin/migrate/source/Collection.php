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
      'access_url' => $this->t('Access URL'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['og'] = $query->leftJoin("{$this->dbName}.og", 'og', "{$this->alias['node']}.nid = %alias.nid");
    $this->alias['repository_url'] = $query->leftJoin("{$this->dbName}.content_field_repository_url", 'repository_url', "{$this->alias['repository']}.vid = %alias.vid");

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
      ->fields($this->alias['og'], ['og_description'])
      ->fields($this->alias['community'], ['field_community_url_url'])
      ->fields($this->alias['repository_url'], ['field_repository_url_url']);

    $query->addExpression("{$this->alias['uri']}.field_id_uri_value", 'uri');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$abstract = $row->getSourceProperty('abstract')) {
      // Fallback to community abstract, if available.
      $row->setSourceProperty('abstract', $row->getSourceProperty('og_description'));
    }

    // Cascade try to get a non-empty access URL.
    if (!$access_url = $row->getSourceProperty('access_url')) {
      if (!$access_url = $row->getSourceProperty('field_community_url_url')) {
        $access_url = $row->getSourceProperty('field_repository_url_url');
      }
    }
    $row->setSourceProperty('access_url', $access_url);

    return parent::prepareRow($row);
  }

}
