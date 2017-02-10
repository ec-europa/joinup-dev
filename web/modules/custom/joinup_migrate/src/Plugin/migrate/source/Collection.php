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

  use ContactTrait;
  use CountryTrait;
  use ElibraryCreationTrait;
  use OwnerTrait;
  use MappingTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'uri' => $this->t('URI'),
      'new_collection' => $this->t('New collection?'),
      'policy2' => $this->t('Policy domain'),
      'abstract' => $this->t('Abstract'),
      'access_url' => $this->t('Access URL'),
      'created_time' => $this->t('Creation date'),
      'body' => $this->t('Description'),
      'elibrary' => $this->t('eLibrary creation'),
      'changed_time' => $this->t('Last changed date'),
      'owner' => $this->t('Owner'),
      'country' => $this->t('Spatial coverage'),
      'affiliates' => $this->t('Affiliates'),
      'contact' => $this->t('Contact info'),
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
        'policy2',
        'abstract',
        'elibrary',
      ])
      ->fields($this->alias['node'], [
        'nid',
        'vid',
        'type',
        'created',
        'changed',
      ])
      ->fields($this->alias['og'], ['og_description'])
      ->fields($this->alias['community'], ['field_community_url_url'])
      ->fields($this->alias['repository_url'], ['field_repository_url_url'])
      ->fields($this->alias['node_revision'], ['body']);

    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.created, '%Y-%m-%dT%H:%i:%s')", 'created_time');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.changed, '%Y-%m-%dT%H:%i:%s')", 'changed_time');

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
    if (!$row->getSourceProperty('created_time')) {
      $row->setSourceProperty('created_time', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }
    // Assure a changed date.
    if (!$row->getSourceProperty('changed_time')) {
      $row->setSourceProperty('changed_time', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }

    // Get affiliates.
    $affiliates = Database::getConnection()->select('joinup_migrate_mapping', 'j')
      ->fields('j', ['nid'])
      ->orderBy('j.collection')
      ->condition('j.migrate', 1)
      ->condition('j.collection', $collection)
      ->condition('j.type', 'asset_release')
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('affiliates', $affiliates);

    // Owner.
    $owner = $this->getCollectionOwners($collection) ?: NULL;
    $row->setSourceProperty('owner', $owner);
    if (!$owner) {
      $this->migration->getIdMap()->saveMessage(['collection' => $collection], "No owner for '$collection'");
    }

    // Contacts.
    $row->setSourceProperty('contact', $this->getCollectionContacts($collection) ?: NULL);

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getSpatialCoverage($row));

    // Elibrary creation.
    $this->elibraryCreation($row);

    return parent::prepareRow($row);
  }

  /**
   * Gets the spatial coverage for a collection.
   *
   * @param \Drupal\migrate\Row $row
   *   The source row.
   *
   * @return string[]
   *   A list of country names.
   */
  protected function getSpatialCoverage(Row $row) {
    // The country list is inherited from corresponding Drupal 6 node.
    if (in_array($row->getSourceProperty('type'), ['repository', 'community'])) {
      $vids = [$row->getSourceProperty('vid')];
    }
    // The country list is compiled from the compounding content-types.
    else {
      $query = Database::getConnection()->select('joinup_migrate_mapping', 'm')
        ->distinct()
        ->fields('n', ['vid'])
        ->condition('m.collection', $row->getSourceProperty('collection'))
        ->condition('n.type', ['asset_release'], 'IN')
        ->condition('m.migrate', 1)
        ->isNotNull('m.nid');
      $query->join(JoinupSqlBase::getSourceDbName() . '.node', 'n', 'm.nid = n.nid');
      $vids = $query->execute()->fetchCol();
    }

    return $vids ? $this->getCountries($vids) : [];
  }

}
