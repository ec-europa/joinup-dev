<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;

/**
 * Prepares the collection migration.
 *
 * @MigrateSource(
 *   id = "prepare"
 * )
 */
class Prepare extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'Prepare';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['collection' => ['type' => 'string']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('Collection'),
      'nid' => $this->t('Node ID'),
      'type' => $this->t('Node type'),
      'new_collection' => $this->t('New collection'),
      'policy2' => $this->t('Level2 policy domain'),
      'abstract' => $this->t('Abstract'),
      'owner' => $this->t('Owner'),
      'logo' => $this->t('Logo'),
      'banner' => $this->t('Banner'),
      'elibrary' => $this->t('Elibrary creation'),
      'pre_moderation' => $this->t('Pre Moderation'),
      'collection_state' => $this->t('Collection state'),
      'status' => $this->t('Status'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $map = $this->migration->getIdMap();
    $publisher = [
      'asset_release' => ['content_field_asset_publisher', 'field_asset_publisher_nid'],
      'repository' => ['content_field_repository_publisher', 'field_repository_publisher_nid'],
    ];
    $contact = [
      'asset_release' => ['content_type_asset_release', 'field_asset_contact_point_nid'],
      'repository' => ['content_type_repository', 'field_repository_contact_point_nid'],
    ];

    $db = Database::getConnection();
    $source = Database::getConnection('default', 'migrate');

    // Build a list of collections that have at least 1 row with 'del' == 'Yes'.
    $allowed = $db->select('joinup_migrate_mapping', 'm', ['fetch' => \PDO::FETCH_ASSOC])
      ->fields('m', ['collection'])
      ->condition('m.del', 'No')
      ->condition('m.collection', ['', '#N/A'], 'NOT IN')
      ->isNotNull('m.policy2')
      ->groupBy('m.collection')
      ->orderBy('m.collection', 'ASC')
      ->execute()
      ->fetchCol();

    $query = $db->select('joinup_migrate_mapping', 'm', ['fetch' => \PDO::FETCH_ASSOC])
      ->fields('m', array_keys($this->fields()))
      ->fields('n', ['vid'])
      ->condition('m.collection', $allowed, 'IN')
      ->orderBy('m.collection', 'ASC');
    $query->leftJoin(JoinupSqlBase::getSourceDbName() . '.node', 'n', 'm.nid = n.nid');

    $collections = [];
    foreach ($query->execute()->fetchAll() as $row) {
      if (!isset($collections[$row['collection']])) {
        $collections[$row['collection']] = [
          'collection' => $row['collection'],
        ];
      }

      // New collections.
      if ($row['new_collection'] === 'Yes') {
        $collections[$row['collection']]['nid'] = 0;
        $collections[$row['collection']]['type'] = '';

        if (!empty($row['abstract'])) {
          $collections[$row['collection']]['abstract'] = $row['abstract'];
        }
        if (!empty($row['logo'])) {
          $collections[$row['collection']]['logo'] = $row['logo'];
        }
        if (!empty($row['banner'])) {
          $collections[$row['collection']]['banner'] = $row['banner'];
        }
        if (!empty($row['elibrary'])) {
          $collections[$row['collection']]['elibrary'] = (int) $row['elibrary'];
        }
        if (!empty($row['pre_moderation'])) {
          $moderation = $row['pre_moderation'] === 'Yes' ? 1 : 0;
          $collections[$row['collection']]['pre_moderation'] = $moderation;
        }
      }
      // Collections inheriting values from 'community' or 'repository'.
      else {
        if (in_array($row['type'], ['community', 'repository'])) {
          if (isset($collections[$row['collection']]['nid'])) {
            $map->saveMessage(['collection' => $row['collection']], "On collection '{$row['collection']}' nid {$row['nid']} ({$row['type']}) is overriding existing value {$collections[$row['collection']]['nid']} ({$collections[$row['collection']]['type']}).");
          }
          $collections[$row['collection']]['nid'] = $row['nid'];
          $collections[$row['collection']]['type'] = $row['type'];
        }
      }

      if (!empty($row['policy2'])) {
        $collections[$row['collection']]['policy2'] = $row['policy2'];
      }

      if (!empty($row['owner']) && ($row['owner'] == 'Yes') && in_array($row['type'], array_keys($publisher))) {
        $publishers = $source
          ->select($publisher[$row['type']][0])
          ->fields($publisher[$row['type']][0], [$publisher[$row['type']][1]])
          ->condition($publisher[$row['type']][0] . '.vid', $row['vid'])
          ->execute()
          ->fetchCol();
        if ($publishers) {
          $collections[$row['collection']]['publisher'] = '|' . implode('|', $publishers) . '|';
        }
        $contacts = $source
          ->select($contact[$row['type']][0])
          ->fields($contact[$row['type']][0], [$contact[$row['type']][1]])
          ->condition($contact[$row['type']][0] . '.vid', $row['vid'])
          ->execute()
          ->fetchCol();
        if ($contacts) {
          $collections[$row['collection']]['contact'] = '|' . implode('|', $contacts) . '|';
        }
      }
    }

    return new \ArrayIterator($collections);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($row->getSourceProperty('nid') === NULL) {
      $this->migration->getIdMap()->saveMessage($row->getSourceIdValues(), "Collection '{$row->getSourceProperty('collection')}' should inherit data from D6 but has no 'community' or 'repository' records defined.");
    }
    return parent::prepareRow($row);
  }

}
