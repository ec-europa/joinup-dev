<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Database;
use Drupal\migrate\Row;

/**
 * Migrates collections.
 *
 * @MigrateSource(
 *   id = "collection"
 * )
 */
class Collection extends CollectionBase {

  use OwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'uri' => $this->t('URI'),
      'new_collection' => $this->t('New collection?'),
      'policy' => $this->t('Policy domain'),
      'abstract' => $this->t('Abstract'),
      'access_url' => $this->t('Access URL'),
      'created' => $this->t('Creation date'),
      'body' => $this->t('Description'),
      'elibrary' => $this->t('eLibrary creation'),
      'pre_moderation' => $this->t('Pre moderation'),
      'changed' => $this->t('Last changed date'),
      'owner' => $this->t('Owner'),
      // @todo Insert here spatial coverage.
      // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2950
      'collection_state' => $this->t('Collection state'),
      'affiliates' => $this->t('Affiliates'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['og'] = $query->leftJoin("{$this->getSourceDbName()}.og", 'og', "{$this->alias['node']}.nid = %alias.nid");
    $this->alias['repository_url'] = $query->leftJoin("{$this->getSourceDbName()}.content_field_repository_url", 'repository_url', "{$this->alias['repository']}.vid = %alias.vid");
    $this->alias['node_revision'] = $query->leftJoin("{$this->getSourceDbName()}.node_revisions", 'node_revision', "{$this->alias['node']}.vid = %alias.vid");

    $query
      ->fields('j', [
        'collection',
        'new_collection',
        'policy',
        'abstract',
        'elibrary',
        'pre_moderation',
        'owner',
        'collection_state',
      ])
      ->fields($this->alias['node'], ['nid', 'type', 'created', 'changed'])
      ->fields($this->alias['og'], ['og_description'])
      ->fields($this->alias['community'], ['field_community_url_url'])
      ->fields($this->alias['repository_url'], ['field_repository_url_url'])
      ->fields($this->alias['node_revision'], ['body']);

    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.created, '%Y-%m-%dT%H:%i:%s')", 'created');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.changed, '%Y-%m-%dT%H:%i:%s')", 'changed');

    return $query
      // Assure the URI field.
      ->addTag('uri');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $collection = $row->getSourceProperty('collection');

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
    if ($access_url) {
      if (!UrlHelper::isValid($access_url)) {
        // Don't import malformed URLs.
        $access_url = NULL;
      }
      elseif (parse_url($access_url, PHP_URL_SCHEME) === NULL) {
        // Needs a full-qualified URL.
        $access_url = "http://$access_url";
      }
      $row->setSourceProperty('access_url', $access_url);
    }

    // Assure a created date.
    if (!$row->getSourceProperty('created')) {
      $row->setSourceProperty('created', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }
    // Assure a changed date.
    if (!$row->getSourceProperty('changed')) {
      $row->setSourceProperty('changed', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }

    // Get affiliates.
    $affiliates = Database::getConnection()->select('joinup_migrate_mapping', 'j')
      ->fields('j', ['nid'])
      ->orderBy('j.collection')
      ->condition('j.del', 'No')
      ->condition('j.collection', $collection)
      ->condition('j.type', 'asset_release')
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('affiliates', $affiliates);

    // Owner.
    $row->setSourceProperty('owner', $this->getCollectionOwners($collection) ?: NULL);

    return parent::prepareRow($row);
  }

}
