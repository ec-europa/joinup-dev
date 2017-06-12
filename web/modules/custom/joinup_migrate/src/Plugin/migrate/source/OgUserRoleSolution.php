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
      ->fields('ou', ['is_admin', 'is_active', 'created'])
      ->fields('our', ['uid', 'rid']);
    $query->join('og_uid', 'ou', 's.nid = ou.nid');
    $query->join('og_users_roles', 'our', 'ou.nid = our.gid AND ou.uid = our.uid');
    // Allow only migrated users.
    $query->join('d8_user', 'u', 'ou.uid = u.uid');

    $rows = [];
    foreach ($query->execute()->fetchAll() as $data) {
      $nid = (int) $data->nid;
      $uid = (int) $data->uid;
      $key = "$nid:$uid";
      if (!isset($rows[$key])) {
        $rows[$key] = [
          'nid' => $nid,
          'uid' => $uid,
          'state' => $data->is_active ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING,
          'created' => (int) $data->created,
          'roles' => [],
        ];
      }
      // Add the solution owner.
      if ($data->is_admin && !in_array('rdf_entity-solution-administrator', $rows[$key]['roles'])) {
        $rows[$key]['roles'][] = 'rdf_entity-solution-administrator';
      }
      // Add the facilitator only if the user is not yet an administrator.
      if (!in_array('rdf_entity-solution-administrator', $rows[$key]['roles']) && !in_array('rdf_entity-solution-facilitator', $rows[$key]['roles'])) {
        $rows[$key]['roles'][] = 'rdf_entity-solution-facilitator';
      }
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
