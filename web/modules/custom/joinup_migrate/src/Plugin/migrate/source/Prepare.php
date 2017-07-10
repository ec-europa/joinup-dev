<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Serialization\Json;
use Drupal\migrate\Row;

/**
 * Prepares the collection migration.
 *
 * @MigrateSource(
 *   id = "prepare"
 * )
 */
class Prepare extends TestableSpreadsheetBase {

  /**
   * A list of 'community' node revision IDs with creation flags deactivated.
   *
   * @var int[]
   */
  protected $deactivatedCreationFlags;

  /**
   * A list of Email contacts.
   *
   * @var string[]
   */
  protected $emailContact;

  /**
   * A list of publishers.
   *
   * @var int[]
   */
  protected $publisher;

  /**
   * A list of contacts.
   *
   * @var int[]
   */
  protected $contact;

  /**
   * A list of imported users.
   *
   * @var int[]
   */
  protected $importedUsers;

  /**
   * A list of OG roles users.
   *
   * @var int[]
   */
  protected $ogUserRoles;

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
        $messages[] = "Node with ID '$nid' doesn't exist or is not of type 'project_project', 'community', 'repository'";
      }
    }

    // Elibrary on 'community' should be computed.
    $this->setElibraryCreation($row, $vid);

    // Process the publisher and contact point for 'repository'.
    $this->setPublisher($row, $vid);
    $this->setContact($row, $vid);

    // Add E-mail contact, if case.
    $this->setContactEmail($row, $nid);

    // The collection admin.
    $this->setCollectionUserRoles($row);

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

  /**
   * Sets the collection OG roles.
   *
   * @param array $row
   *   The iterator current row.
   */
  protected function setCollectionUserRoles(array &$row) {
    $roles = [];

    // Get only once and statically cache the list of imported users.
    if (!isset($this->importedUsers)) {
      $this->importedUsers = $this->db->select('d8_user', 'u')
        ->fields('u', ['mail', 'uid'])
        ->execute()
        ->fetchAllKeyed();
    }

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $collection_owner = trim((string) $row['Collection Owner']);
    if ($collection_owner) {
      // Collection owners is a comma separated string of E-mails.
      $collection_owner = array_filter(array_map('trim', explode(',', $collection_owner)));
      // Convert E-mails to UIDs.
      $uids = array_filter(array_map(function ($mail) {
        return isset($this->importedUsers[$mail]) ? $this->importedUsers[$mail] : NULL;
      }, $collection_owner));

      if ($uids) {
        $request_time = \Drupal::time()->getRequestTime();
        $roles['admin'] = array_fill_keys($uids, [1, $request_time]);
      }
    }

    // Get only once and statically cache OG data from Drupal 6 backend.
    if (!isset($this->ogUserRoles)) {
      /** @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = $this->db->select('d8_mapping', 'm')
        ->fields('m', ['collection'])
        ->fields('ou', ['is_admin', 'is_active', 'created'])
        ->fields('our', ['uid', 'rid'])
        ->orderBy('m.collection')
        ->condition('m.owner', ['Y', 'Yes'], 'IN');
      $query->join('og_uid', 'ou', 'm.nid = ou.nid');
      $query->join('og_users_roles', 'our', 'ou.nid = our.gid AND ou.uid = our.uid');
      // Only migrated users are allowed.
      $query->join('d8_user', 'u', 'ou.uid = u.uid');

      $this->ogUserRoles = [];
      foreach ($query->execute()->fetchAll() as $data) {
        $collection = $data->collection;
        $uid = (int) $data->uid;
        $rid = (int) $data->rid;
        $created = (int) $data->created;
        $is_active = (int) $data->is_active;

        // Drupal 6 admin role, which is 'facilitator' in Drupal 8, is computed
        // in a different way.
        if ($data->is_admin && !isset($this->ogUserRoles[$collection][static::$roleMap[6]][$uid])) {
          $this->ogUserRoles[$collection][static::$roleMap[6]][$uid] = [$is_active, $created];
        }
        // Add as member only if it was not previously added as 'facilitator'.
        if (!isset($this->ogUserRoles[$collection][static::$roleMap[6]][$uid])) {
          $this->ogUserRoles[$collection][static::$roleMap[$rid]][$uid] = [$is_active, $created];
        }
      }
    }

    $data = isset($this->ogUserRoles[$row['Collection_name']]) ? $this->ogUserRoles[$row['Collection_name']] : [];
    if (isset($roles['admin'])) {
      // Remove facilitators and members that are already owners/admins.
      foreach ($data as $role => &$users) {
        $users = array_diff_key($users, $roles['admin']);
        if (empty($users)) {
          unset($data[$role]);
        }
      }
    }
    $roles += $data;

    // Add roles to row.
    if ($roles) {
      $row['roles'] = Json::encode($roles);
    }
  }

  /**
   * Computes the elibrary creation.
   *
   * @param array $row
   *   The iterator current row.
   * @param int|null $vid
   *   The node revision ID or NULL.
   */
  protected function setElibraryCreation(array &$row, $vid) {
    if (($row['type'] !== 'community') || !$vid) {
      return;
    }

    if (!isset($this->deactivatedCreationFlags)) {
      $this->deactivatedCreationFlags = $this->db->select('content_type_community', 'c')
        ->fields('c', ['vid'])
        ->condition('c.field_community_forum_creation_value', 'Deactivated')
        ->condition('c.field_community_wiki_creation_value', 'Deactivated')
        ->condition('c.field_community_news_creation_value', 'Deactivated')
        ->condition('c.field_community_documents_creati_value', 'Deactivated')
        ->execute()
        ->fetchCol();
    }

    if (in_array($vid, $this->deactivatedCreationFlags)) {
      $row['elibrary'] = 0;
    }
  }

  /**
   * Sets the contact Email.
   *
   * @param array $row
   *   The iterator current row.
   * @param int|null $nid
   *   The node ID or NULL.
   */
  protected function setContactEmail(array &$row, $nid) {
    if (!$nid || ($row['type'] !== 'project_project')) {
      return;
    }

    if (!isset($this->emailContact)) {
      /** @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = $this->db->select('node', 'n')
        ->fields('n', ['nid'])
        ->fields('c', ['field_project_common_contact_value'])
        ->isNotNull('c.field_project_common_contact_value');
      $query->join('content_field_project_common_contact', 'c', 'n.vid = c.vid');
      $this->emailContact = $query->execute()->fetchAllKeyed();
    }

    if (isset($this->emailContact[$nid])) {
      $row['contact_email'] = $this->emailContact[$nid];
    }
  }

  /**
   * Sets the publishers.
   *
   * @param array $row
   *   The iterator current row.
   * @param int|null $vid
   *   The node revision ID or NULL.
   */
  protected function setPublisher(array &$row, $vid) {
    if (!$vid || ($row['type'] !== 'repository')) {
      return;
    }

    if (!isset($this->publisher)) {
      $result = $this->db->select('content_field_repository_publisher', 'p')
        ->fields('p', ['vid'])
        ->fields('p', ['field_repository_publisher_nid'])
        ->execute()
        ->fetchAll();
      foreach ($result as $item) {
        $this->publisher[(int) $item->vid][] = $item->field_repository_publisher_nid;
      }
    }

    if (!empty($this->publisher[$vid])) {
      $row['publisher'] = implode(',', $this->publisher[$vid]);
    }
  }

  /**
   * Sets the contact.
   *
   * @param array $row
   *   The iterator current row.
   * @param int|null $vid
   *   The node revision ID or NULL.
   */
  protected function setContact(array &$row, $vid) {
    if (!$vid || ($row['type'] !== 'repository')) {
      return;
    }

    if (!isset($this->contact)) {
      $result = $this->db->select('content_type_repository', 'c')
        ->fields('c', ['vid'])
        ->fields('c', ['field_repository_contact_point_nid'])
        ->isNotNull('c.field_repository_contact_point_nid')
        ->execute()
        ->fetchAll();
      foreach ($result as $item) {
        $this->contact[(int) $item->vid][] = $item->field_repository_contact_point_nid;
      }
    }

    if (!empty($this->contact[$vid])) {
      $row['contact'] = implode(',', $this->contact[$vid]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $roles = Json::decode($row->getSourceProperty('roles'));
    if (!isset($roles['admin'])) {
      $row_index = $row->getSourceProperty('row_index');
      $collection = $row->getSourceProperty('Collection_name');
      $this->migration->getIdMap()->saveMessage($row->getSourceIdValues(), "Row: $row_index, Collection: '$collection': The collection owner is missed or the user was not migrated");
    }

    return parent::prepareRow($row);
  }

  /**
   * Roles mapping.
   *
   * @var string[]
   */
  protected static $roleMap = [
    // 'administrator‘.
    6 => 'facilitator',
    // 'facilitator'.
    4 => 'member',
    // 'contributor'.
    18 => 'member',
    // 'developer'.
    9 => 'member',
    // 'release manager'.
    17 => 'member',
    // 'member'.
    5 => 'member',
  ];

}
