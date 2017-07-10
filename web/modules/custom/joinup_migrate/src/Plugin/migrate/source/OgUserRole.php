<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Serialization\Json;
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $rows = [];

    $role_type = [
      'admin' => 'rdf_entity-collection-administrator',
      'facilitator' => 'rdf_entity-collection-facilitator',
      'member' => 'rdf_entity-collection-member',
    ];

    $query = Database::getConnection('default', 'migrate')
      ->select('d8_prepare', 'c')
      ->fields('c', ['collection', 'roles']);

    $info = [];
    foreach ($query->execute()->fetchAll() as $data) {
      if ($data->roles) {
        $roles = Json::decode($data->roles);
        foreach ($roles as $type => $uids) {
          foreach ($uids as $uid => list($is_active, $created)) {
            $info[$data->collection][$uid]['created'] = $created;
            $info[$data->collection][$uid]['state'][] = $is_active ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING;
            $info[$data->collection][$uid]['roles'][] = $role_type[$type];
          }
        }
      }
    }

    foreach ($info as $collection => $users) {
      foreach ($users as $uid => $data) {
        // The facilitator role is complementary with the administrator role.
        if (in_array('rdf_entity-collection-administrator', $data['roles'])) {
          $data['roles'][] = 'rdf_entity-collection-facilitator';
        }
        $rows[] = [
          'uid' => $uid,
          'collection' => $collection,
          'roles' => $data['roles'],
          'created' => $data['created'],
          'state' => $data['state'],
        ];
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'collection_user_role';
  }

}
