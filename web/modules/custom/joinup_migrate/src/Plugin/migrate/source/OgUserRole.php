<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\og\OgMembershipInterface;

/**
 * Migrates collection OG user-roles.
 *
 * @MigrateSource(
 *   id = "og_user_role"
 * )
 */
class OgUserRole extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'collection' => [
        'type' => 'string',
      ],
      'uid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uid' => $this->t('User ID'),
      'collection' => $this->t('Collection'),
      'roles' => $this->t('OG roles'),
      'created' => $this->t('Created'),
      'state' => $this->t('State'),
      'id' => $this->t('Membership ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    static $multiple_owner_rows_reported = [];

    $db = Database::getConnection('default', 'migrate');

    $query = $db->select('d8_prepare', 'p')
      ->fields('p', ['collection'])
      ->fields('ou', ['uid', 'is_admin', 'is_active', 'created']);
    $query->join('d8_mapping', 'm', "p.collection = m.collection AND m.owner = 'Y'");
    $query->join('og_uid', 'ou', 'm.nid = ou.nid');
    // Allow only migrated users.
    $query->join('d8_user', 'u', 'ou.uid = u.uid');

    $rows = [];
    foreach ($query->execute()->fetchAll() as $data) {
      $collection_name = $data->collection;
      $uid = (int) $data->uid;
      $key = "$collection_name:$uid";

      // In the case when we have multiple rows with "Owner == 'Y'", we pickup
      // only the first occurrence. For this reason we key the $rows array with
      // the unique key compounded by collection name and user ID.
      if (isset($rows[$key])) {
        if (!isset($multiple_owner_rows_reported[$key])) {
          // Log only once, even there more than 2 owner rows per collection.
          $this->migration->getIdMap()->saveMessage([
            'collection' => $collection_name,
            'uid' => $uid,
          ], "Collection '$collection_name' has multiple rows with Owner == 'Y'");
        }
        $multiple_owner_rows_reported[$key] = TRUE;
        continue;
      }

      $row = [
        'collection' => $collection_name,
        'uid' => $uid,
        'state' => $data->is_active ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING,
        'created' => (int) $data->created,
        'roles' => [],
      ];

      // Collection owners are added as facilitators.
      if ($data->is_admin) {
        $row['roles']['facilitator'] = 'rdf_entity-collection-facilitator';
      }

      $rows[$key] = $row;
    }

    // Add the collection owners.
    $query = $db->select('d8_prepare', 'p')
      ->fields('p', ['collection', 'collection_owner'])
      ->isNotNull('p.collection_owner');
    foreach ($query->execute()->fetchAllKeyed() as $collection_name => $collection_owner) {
      $uids = array_map('intval', explode(',', $collection_owner));
      foreach ($uids as $delta => $uid) {
        $key = "$collection_name:$uid";
        // Membership already added.
        if (isset($rows[$key])) {
          $rows[$key]['roles']['owner'] = 'rdf_entity-collection-administrator';
          // Facilitator should be added along with owner.
          $rows[$key]['roles']['facilitator'] = 'rdf_entity-collection-facilitator';
        }
        // New membership.
        else {
          $rows[$key] = [
            'collection' => $collection_name,
            'uid' => $uid,
            'state' => OgMembershipInterface::STATE_ACTIVE,
            'roles' => [
              'owner' => 'rdf_entity-collection-administrator',
              'facilitator' => 'rdf_entity-collection-facilitator',
            ],
          ];
        }
      }
    }

    // Get all existing memberships using a SQL query to speed up the process.
    $existing_memberships = [];
    if (Database::getConnection()->schema()->tableExists('migrate_map_collection')) {
      /** @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = Database::getConnection()->select('og_membership', 'og')
        ->condition('og.entity_type', 'rdf_entity');
      $query->join('migrate_map_collection', 'c', "og.entity_id = c.destid1");
      $query->addField('og', 'id');
      $query->addExpression("CONCAT_WS(':', c.sourceid1, og.uid)");
      $existing_memberships = $query->execute()->fetchAllKeyed(1, 0);
    }

    array_walk($rows, function (array &$row, $key) use ($existing_memberships) {
      // It's possible that a membership has been already created for this user,
      // as an effect of his collection authorship. In this case we add also the
      // membership ID to the row, so the membership will be updated instead of
      // being created.
      if (isset($existing_memberships[$key])) {
        $row['id'] = (int) $existing_memberships[$key];
      }
      // Remove keys from roles array.
      $row['roles'] = array_values($row['roles']);
    });

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'collection_user_role';
  }

}
