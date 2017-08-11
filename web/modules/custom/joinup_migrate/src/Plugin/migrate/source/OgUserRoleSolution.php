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
      'id' => $this->t('Membership ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $db = Database::getConnection('default', 'migrate');

    // Get all existing memberships using a SQL query to speed up the process.
    $existing_memberships = [];
    if (Database::getConnection()->schema()->tableExists('migrate_map_solution')) {
      /** @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = Database::getConnection()->select('og_membership', 'og')
        ->condition('og.entity_type', 'rdf_entity');
      $query->join('migrate_map_solution', 's', "og.entity_id = s.destid1");
      $query->addField('og', 'id');
      $query->addExpression("CONCAT_WS(':', s.sourceid1, og.uid)");
      $existing_memberships = $query->execute()->fetchAllKeyed(1, 0);
    }

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

      // It's possible that a membership has been already created for this user,
      // as an effect of his solution authorship. In this case we add also the
      // membership ID to the row, so the membership will be updated instead of
      // being created.
      $key = "$nid:$uid";
      if (isset($existing_memberships[$key])) {
        $row['id'] = (int) $existing_memberships[$key];
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
