<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\og\OgMembershipInterface;

/**
 * Migrates solution OG user-roles.
 *
 * @MigrateSource(
 *   id = "og_user_role_solution"
 * )
 */
class OgUserRoleSolution extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
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
      'nid' => $this->t('Solution'),
      'uid' => $this->t('User ID'),
      'roles' => $this->t('OG roles'),
      'created' => $this->t('Created'),
      'state' => $this->t('State'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $db = Database::getConnection('default', 'migrate');

    $query = $db->select('d8_solution', 's')
      ->fields('s', ['nid'])
      ->fields('ou', ['uid', 'is_admin', 'is_active', 'created']);
    $query->join('og_uid', 'ou', 's.nid = ou.nid');
    // Allow only migrated users.
    $query->join('d8_user', 'u', 'ou.uid = u.uid');

    $rows = [];
    foreach ($query->execute()->fetchAll() as $data) {
      $nid = (int) $data->nid;
      $uid = (int) $data->uid;
      $row = [
        'nid' => $nid,
        'uid' => $uid,
        'state' => $data->is_active ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING,
        'created' => (int) $data->created,
        // Each member, regardless of its role in D6, is facilitator in D8.
        'roles' => ['rdf_entity-solution-facilitator'],
      ];
      // Add the solution owner, if case.
      if ($data->is_admin) {
        $row['roles'][] = 'rdf_entity-solution-administrator';
      }
      $rows[] = $row;
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'solution_user_role';
  }

}
