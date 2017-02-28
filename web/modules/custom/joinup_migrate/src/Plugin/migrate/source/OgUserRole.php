<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

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
      ->select('joinup_migrate_collection', 'c')
      ->fields('c', ['collection', 'roles']);

    $info = [];
    foreach ($query->execute()->fetchAll() as $data) {
      $roles = Json::decode($data->roles);
      if ($roles) {
        foreach ($roles as $type => $uids) {
          foreach ($uids as $uid => $created) {
            $info[$data->collection][$uid]['created'] = $created;
            $info[$data->collection][$uid]['roles'][] = $role_type[$type];
          }
        }
      }
    }

    foreach ($info as $collection => $users) {
      foreach ($users as $uid => $data) {
        $rows[] = [
          'uid' => $uid,
          'collection' => $collection,
          'roles' => $data['roles'],
          'created' => $data['created'],
        ];
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'collection-user-role';
  }

}
