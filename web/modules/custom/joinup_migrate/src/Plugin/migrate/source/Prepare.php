<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

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
      'policy' => $this->t('Level1 policy domain'),
      'policy2' => $this->t('Level2 policy domain'),
      'abstract' => $this->t('Abstract'),
      'owner' => $this->t('Owner'),
      'logo' => $this->t('Logo'),
      'banner' => $this->t('Banner'),
      'elibrary' => $this->t('Elibrary creation'),
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

    // Build a list of collections that have at least 1 row with 'migrate' == 1.
    $allowed = $db->select('joinup_migrate_mapping', 'm', ['fetch' => \PDO::FETCH_ASSOC])
      ->fields('m', ['collection'])
      ->condition('m.migrate', 1)
      ->condition('m.collection', ['', '#N/A'], 'NOT IN')
      ->isNotNull('m.policy2')
      ->groupBy('m.collection')
      ->orderBy('m.collection', 'ASC')
      ->execute()
      ->fetchCol();

    $fields = $this->fields();
    unset($fields['status']);
    $query = $db->select('joinup_migrate_mapping', 'm', ['fetch' => \PDO::FETCH_ASSOC])
      ->fields('m', array_keys($fields))
      ->fields('n', ['vid'])
      ->orderBy('m.collection', 'ASC');

    if ($allowed) {
      $query->condition('m.collection', $allowed, 'IN');
    }
    else {
      // Return an empty set if there are no eligible collections.
      $query->condition(1, 2);
    }

    $query->leftJoin(JoinupSqlBase::getSourceDbName() . '.node', 'n', 'm.nid = n.nid');

    $collections = [];
    foreach ($query->execute()->fetchAll() as $row) {
      $collection = $row['collection'];
      if (!isset($collections[$collection])) {
        $collections[$collection] = [
          'collection' => $collection,
        ];
      }

      // New collections.
      if ($row['new_collection'] === 'Yes') {
        $collections[$collection]['nid'] = 0;
        $collections[$collection]['type'] = '';

        if (!empty($row['abstract'])) {
          $collections[$collection]['abstract'] = $row['abstract'];
        }
        if (!empty($row['logo'])) {
          $collections[$collection]['logo'] = $row['logo'];
        }
        if (!empty($row['banner'])) {
          $collections[$collection]['banner'] = $row['banner'];
        }
        if (!empty($row['elibrary'])) {
          $collections[$collection]['elibrary'] = (int) $row['elibrary'];
        }
      }
      // Collections inheriting values from 'community' or 'repository'.
      else {
        if (in_array($row['type'], ['community', 'repository'])) {
          if (isset($collections[$collection]['nid'])) {
            $map->saveMessage(['collection' => $collection], "On collection '$collection' nid {$row['nid']} ({$row['type']}) is overriding existing value {$collections[$collection]['nid']} ({$collections[$collection]['type']}).");
          }
          $collections[$collection]['nid'] = $row['nid'];
          $collections[$collection]['type'] = $row['type'];
        }
      }

      if (!empty($row['policy2'])) {
        $collections[$collection]['policy2'] = $row['policy2'];
      }

      if (!empty($row['owner']) && ($row['owner'] == 'Yes') && in_array($row['type'], array_keys($publisher))) {
        $publishers = $source
          ->select($publisher[$row['type']][0])
          ->fields($publisher[$row['type']][0], [$publisher[$row['type']][1]])
          ->condition($publisher[$row['type']][0] . '.vid', $row['vid'])
          ->execute()
          ->fetchCol();
        if ($publishers) {
          $collections[$collection]['publisher'] = '|' . implode('|', $publishers) . '|';
        }
        $contacts = $source
          ->select($contact[$row['type']][0])
          ->fields($contact[$row['type']][0], [$contact[$row['type']][1]])
          ->condition($contact[$row['type']][0] . '.vid', $row['vid'])
          ->execute()
          ->fetchCol();
        if ($contacts) {
          $collections[$collection]['contact'] = '|' . implode('|', $contacts) . '|';
        }
      }
    }

    foreach ($collections as $collection => $data) {
      // New collections's nid is 0. Collections with a NULL nid are collections
      // inheriting their data (abstract, etc.) from a Drupal 6 'community' or
      // 'repository' but not containing any 'community' or 'repository'. Such
      // cases should not be migrated and the error should be logged.
      if (!isset($data['nid'])) {
        $map->saveMessage(['collection' => $collection], "Collection '$collection' should inherit data from D6 but has no 'community' or 'repository' records defined.");
        unset($collections[$collection]);
      }
    }

    return new \ArrayIterator($collections);
  }

}
