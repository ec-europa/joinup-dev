<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Serialization\Json;
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
      'policy' => $this->t('Level1 policy domain'),
      'policy2' => $this->t('Level2 policy domain'),
      'abstract' => $this->t('Abstract'),
      'owner' => $this->t('Owner'),
      'owner_name' => $this->t('Owner name'),
      'owner_type' => $this->t('Owner type'),
      'collection_owner' => $this->t('Collection owner'),
      'logo' => $this->t('Logo'),
      'banner' => $this->t('Banner'),
      'elibrary' => $this->t('Elibrary creation'),
      'state' => $this->t('State'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $publisher = [
      'asset_release' => ['content_field_asset_publisher', 'field_asset_publisher_nid'],
      'repository' => ['content_field_repository_publisher', 'field_repository_publisher_nid'],
    ];
    $contact = [
      'asset_release' => ['content_type_asset_release', 'field_asset_contact_point_nid'],
      'repository' => ['content_type_repository', 'field_repository_contact_point_nid'],
    ];

    $db = Database::getConnection('default', 'migrate');

    // Build a list of collections that have at least 1 row with 'migrate' == 1.
    $allowed = $db->select('d8_mapping', 'm', ['fetch' => \PDO::FETCH_ASSOC])
      ->fields('m', ['collection'])
      ->condition('m.migrate', 1)
      ->condition('m.collection', ['', '#N/A'], 'NOT IN')
      ->isNotNull('m.policy2')
      ->groupBy('m.collection')
      ->orderBy('m.collection', 'ASC')
      ->execute()
      ->fetchCol();

    $fields = $this->fields();
    unset($fields['elibrary']);
    $query = $db->select('d8_mapping', 'm', ['fetch' => \PDO::FETCH_ASSOC])
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

    $query->leftJoin('node', 'n', 'm.nid = n.nid');

    $collections = [];
    foreach ($query->execute()->fetchAll() as $row) {
      $collection = $row['collection'];
      if (!isset($collections[$collection])) {
        $node_collections = [];
        $collections[$collection] = [
          'collection' => $collection,
          'elibrary' => NULL,
        ];
        $new_collection = $db->select('d8_mapping', 'm')
          ->fields('m', ['new_collection'])
          ->condition('m.collection', $collection)
          ->condition('m.migrate', 1)
          ->condition('m.collection', ['', '#N/A'], 'NOT IN')
          ->isNotNull('m.policy2')
          ->groupBy('m.new_collection')
          ->execute()
          ->fetchCol();
        sort($new_collection);
        if (count($new_collection) === 2 && $new_collection === ['No', 'Yes']) {
          $collections[$collection]['messages'][] = "Collection '$collection' column 'New collection' should be either 'Yes' or 'No'. Both found.";
        }

        if (!empty($row['collection_owner'])) {
          $collections[$collection]['collection_owner'] = $row['collection_owner'];
        }
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
          if (isset($node_collections[$collection])) {
            $collections[$collection]['messages'][] = "Collection '$collection' (nid {$row['nid']}, type {$row['type']}) is overriding existing value created by nid {$node_collections[$collection][0]} ({$node_collections[$collection][1]}).";
          }
          $node_collections[$collection] = [$row['nid'], $row['type']];
          $collections[$collection]['nid'] = $row['nid'];
          $collections[$collection]['type'] = $row['type'];

          // Elibrary on community should be computed.
          if ($row['type'] === 'community') {
            $deactivated = (bool) $db->select('content_type_community', 'c')
              ->fields('c', ['vid'])
              ->condition('c.vid', (int) $row['vid'])
              ->condition('c.field_community_forum_creation_value', 'Deactivated')
              ->condition('c.field_community_wiki_creation_value', 'Deactivated')
              ->condition('c.field_community_news_creation_value', 'Deactivated')
              ->condition('c.field_community_documents_creati_value', 'Deactivated')
              ->execute()
              ->fetchField();
            if ($deactivated) {
              $collections[$collection]['elibrary'] = 0;
            }
          }
        }
      }

      if (!empty($row['policy2'])) {
        $collections[$collection]['policy2'] = $row['policy2'];
      }

      // State.
      if (!isset($collections[$collection]['state']) || $collections[$collection]['state'] !== $row['state']) {
        $collections[$collection]['state'] = empty($row['state']) ? 'validated' : $row['state'];
      }

      $is_owner = !empty($row['owner']) && in_array($row['owner'], ['Yes', 'Y']);

      // OG roles.
      /** @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = $db->select('og_users_roles', 'ur')
        ->fields('ur', ['uid', 'rid'])
        ->fields('u', ['is_admin', 'created'])
        ->condition('ur.gid', (int) $row['nid'])
        ->orderBy('ur.uid');
      $query->join('og_uid', 'u', 'ur.gid = u.nid AND ur.uid = u.uid');
      // Only migrated users are allowed.
      $query->join('d8_user', 'users', 'ur.uid = users.uid');

      foreach ($query->execute()->fetchAll() as $item) {
        $uid = (int) $item->uid;
        $created = (int) $item->created;

        if (!isset($collections[$collection]['roles'])) {
          // Initialize an empty array.
          $collections[$collection]['roles'] = [
            'admin' => [],
            'facilitator' => [],
            'member' => [],
          ];
        }

        // Group owner.
        if ((int) $item->is_admin === 1) {
          $key = $is_owner ? 'admin' : 'facilitator';
          if (!isset($collections[$collection]['roles'][$key][$uid])) {
            $collections[$collection]['roles'][$key][$uid] = $created;
          }
        }
        // Group facilitator.
        if ($item->rid == 4 && !isset($collections[$collection]['roles']['facilitator'][$uid])) {
          $collections[$collection]['roles']['facilitator'][$uid] = $created;
        }
        // Group members.
        if ($item->rid == 5 && !isset($collections[$collection]['roles']['member'][$uid])) {
          $collections[$collection]['roles']['member'][$uid] = $created;
        }
      }

      if ($is_owner && in_array($row['type'], array_keys($publisher))) {
        $publishers = $db
          ->select($publisher[$row['type']][0])
          ->fields($publisher[$row['type']][0], [$publisher[$row['type']][1]])
          ->condition($publisher[$row['type']][0] . '.vid', $row['vid'])
          ->execute()
          ->fetchCol();
        if ($publishers) {
          $collections[$collection]['publisher'] = implode(',', $publishers);
        }
        $contacts = $db
          ->select($contact[$row['type']][0])
          ->fields($contact[$row['type']][0], [$contact[$row['type']][1]])
          ->condition($contact[$row['type']][0] . '.vid', $row['vid'])
          ->execute()
          ->fetchCol();
        if ($contacts) {
          $collections[$collection]['contact'] = implode(',', $contacts);
        }
      }

      // Add text owner and E-mail contact, if case.
      if ($is_owner && ($row['type'] === 'project_project')) {
        if (!empty($row['owner_name']) && !empty($row['owner_type'])) {
          $collections[$collection]['owner_name'] = $row['owner_name'];
          $collections[$collection]['owner_type'] = $row['owner_type'];
        }
        $query = $db->select('node', 'n')
          ->fields('c', ['field_project_common_contact_value'])
          ->isNotNull('c.field_project_common_contact_value')
          ->condition('n.nid', $row['nid']);
        $query->join('content_field_project_common_contact', 'c', 'n.vid = c.vid');
        if ($contact_email = $query->execute()->fetchField()) {
          $collections[$collection]['contact_email'] = $contact_email;
        }
      }
    }

    foreach ($collections as $collection => $data) {
      // Serialize roles.
      if (!empty($collections[$collection]['roles'])) {
        $collections[$collection]['roles'] = Json::encode($collections[$collection]['roles']);
      }

      // New collections's nid is 0. Collections with a NULL nid are collections
      // inheriting their data (abstract, etc.) from a Drupal 6 'community' or
      // 'repository' but not containing any 'community' or 'repository'. Such
      // cases should be logged and this row will be rejected later, in process.
      if (!isset($data['nid'])) {
        $collections[$collection]['messages'][] = "Collection '$collection' should inherit data from D6 but has no 'community' or 'repository' records defined.";
      }
    }

    return new \ArrayIterator($collections);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $collection = $row->getSourceProperty('collection');

    // Log messages collected during iteration.
    if ($messages = $row->getSourceProperty('messages')) {
      foreach ($messages as $message) {
        $this->idMap->saveMessage(['collection' => $collection], $message);
      }
      $row->setSourceProperty('messages', NULL);
    }

    // Only collections with nid strictly equals a valid integers are migrated.
    if ($row->getSourceProperty('nid') === NULL) {
      return FALSE;
    }

    return parent::prepareRow($row);
  }

}
