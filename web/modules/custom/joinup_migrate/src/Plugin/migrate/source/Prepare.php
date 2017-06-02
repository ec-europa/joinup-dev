<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Serialization\Json;

/**
 * Prepares the collection migration.
 *
 * @MigrateSource(
 *   id = "prepare"
 * )
 */
class Prepare extends TestableSpreadsheetBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['Collection_name' => ['type' => 'string']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Node type'),
      'elibrary' => $this->t('Elibrary creation'),
      'publisher' => $this->t('Publisher'),
      'contact' => $this->t('Contact'),
      'contact_email' => $this->t('E-mail contact'),
      'roles' => $this->t('Roles'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  protected function rowIsValid(array &$row) {
    $messages = [];

    // Ensure sane defaults.
    $row += array_fill_keys([
      'type',
      'elibrary',
      'publisher',
      'contact',
      'contact_email',
      'roles',
    ], NULL);

    $collection = $row['Collection_name'];
    $nid = !empty($row['Nid']) ? (int) $row['Nid'] : NULL;
    $vid = NULL;

    // Identify the node type.
    if ($nid) {
      $node = $this->db->select('node', 'n')
        ->fields('n', ['vid', 'type'])
        ->condition('n.nid', $nid)
        ->condition('n.type', ['project_project', 'community', 'repository'], 'IN')
        ->execute()
        ->fetch();
      if ($node) {
        $row['type'] = $node->type;
        $vid = $node->vid;
      }
      else {
        $messages[] = "Node with ID '$nid' doesn't exit or is not of type 'project_project', 'community', 'repository'";
      }
    }

    // Elibrary on community should be computed.
    if ($row['type'] === 'community') {
      $deactivated = (bool) $this->db->select('content_type_community', 'c')
        ->fields('c', ['vid'])
        ->condition('c.vid', $vid)
        ->condition('c.field_community_forum_creation_value', 'Deactivated')
        ->condition('c.field_community_wiki_creation_value', 'Deactivated')
        ->condition('c.field_community_news_creation_value', 'Deactivated')
        ->condition('c.field_community_documents_creati_value', 'Deactivated')
        ->execute()
        ->fetchField();
      if ($deactivated) {
        $row['elibrary'] = 0;
      }
    }

    // Process the publisher and contact point for 'repository'.
    if ($row['type'] === 'repository') {
      $publishers = $this->db->select('content_field_repository_publisher', 'p')
        ->fields('p', ['field_repository_publisher_nid'])
        ->condition('p.vid', $vid)
        ->execute()
        ->fetchCol();
      if ($publishers) {
        $row['publisher'] = implode(',', $publishers);
      }
      $contacts = $this->db->select('content_type_repository', 'c')
        ->fields('c', ['field_repository_contact_point_nid'])
        ->condition('c.vid', $vid)
        ->isNotNull('c.field_repository_contact_point_nid')
        ->execute()
        ->fetchCol();
      if ($contacts) {
        $row['contact'] = implode(',', $contacts);
      }
    }

    // Add E-mail contact, if case.
    if ($row['type'] === 'project_project') {
      $query = $this->db->select('node', 'n')
        ->fields('c', ['field_project_common_contact_value'])
        ->isNotNull('c.field_project_common_contact_value')
        ->condition('n.nid', $nid);
      $query->join('content_field_project_common_contact', 'c', 'n.vid = c.vid');
      if ($contact_email = $query->execute()->fetchField()) {
        $row['contact_email'] = $contact_email;
      }
    }

    // OG roles.
    $roles = [];

    // The collection admin.
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $collection_owner = trim((string) $row['Collection Owner']);
    if ($collection_owner) {
      $collection_owner = array_filter(array_map('trim', explode(',', $collection_owner)));
      $query = $this->db->select('users', 'u')
        ->fields('u', ['uid'])
        ->condition('u.mail', $collection_owner, 'IN');
      // Only migrated users are allowed.
      $query->join('d8_user', 'users', 'u.uid = users.uid');
      if ($uids = $query->execute()->fetchCol()) {
        $roles['admin'] = array_fill_keys($uids, \Drupal::time()->getRequestTime());
      }
    }

    $query = $this->db->select('d8_mapping', 'm')
      ->fields('ur', ['uid', 'rid'])
      ->fields('u', ['is_admin', 'created'])
      ->orderBy('ur.uid')
      ->condition('m.collection', $collection)
      ->condition('m.owner', ['Y', 'Yes'], 'IN');
    $query->join('og_users_roles', 'ur', 'm.nid = ur.gid');
    $query->join('og_uid', 'u', 'ur.gid = u.nid AND ur.uid = u.uid');
    // Only migrated users are allowed.
    $query->join('d8_user', 'users', 'ur.uid = users.uid');

    foreach ($query->execute()->fetchAll() as $item) {
      $uid = (int) $item->uid;
      $created = (int) $item->created;
      foreach ([4 => 'facilitator', 5 => 'member'] as $rid => $role) {
        if ($item->rid == $rid && !isset($roles[$role][$uid])) {
          $roles[$role][$uid] = $created;
        }
      }
    }
    if ($roles) {
      $row['roles'] = Json::encode($roles);
    }

    // Register inconsistencies.
    if ($messages) {
      $row_index = $row['row_index'];
      $source_ids = ['Nid' => $row['Nid']];
      foreach ($messages as $message) {
        $this->migration->getIdMap()->saveMessage($source_ids, "Row: $row_index, Nid: $nid: $message");
      }
    }

    return empty($messages);
  }

}
