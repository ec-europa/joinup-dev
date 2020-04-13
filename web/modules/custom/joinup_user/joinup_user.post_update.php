<?php

/**
 * @file
 * Post update functions for the Joinup User module.
 */

declare(strict_types = 1);

use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Entity\Index;
use Drupal\user\UserInterface;

/**
 * Adds permissions to subscribe to discussions for authenticated users.
 */
function joinup_user_post_update_add_subscribe_discussion_permissions(): void {
  user_role_grant_permissions(UserInterface::AUTHENTICATED_ROLE, [
    'flag subscribe_discussions',
    'unflag subscribe_discussions',
  ]);
}

/**
 * Add the 'access joinup reports' permission to moderators and administrators.
 */
function joinup_user_post_update_joinup_reports(): void {
  foreach (['moderator', 'administrator'] as $rid) {
    user_role_grant_permissions($rid, ['access joinup reports']);
  }
}

/**
 * Grant the authenticated users the 'never autoplay videos' permission.
 */
function joinup_user_post_update_add_never_autoplay_permission(): void {
  user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ['never autoplay videos']);
}

/**
 * Remove the unused 'Professional profile' field.
 */
function joinup_user_post_update_remove_professional_profile(): void {
  // By deleting the field storage, the field instance is also automatically
  // deleted.
  FieldStorageConfig::loadByName('user', 'field_user_professional_profile')->delete();
}

/**
 * Remove the 'post comments' permission.
 */
function joinup_user_post_update_remove_anonymous_post_comments(): void {
  user_role_revoke_permissions(AccountInterface::ANONYMOUS_ROLE, ['post comments']);
}

/**
 * Remove spam accounts.
 */
function joinup_user_post_update_spam_accounts(array &$sandbox): ?string {
  $db = \Drupal::database();

  if (!isset($sandbox['uids'])) {
    // Get all spam account IDs.
    $query = $db->select('users', 'u')->fields('u', ['uid'])->orderBy('u.uid');
    $query->leftJoin('user__field_user_family_name', 'last', 'u.uid = last.entity_id');
    $query->leftJoin('user__field_user_first_name', 'first', 'u.uid = first.entity_id');
    $sandbox['uids'] = $query
      ->condition(
        $query->orConditionGroup()
          ->condition('last.field_user_family_name_value', '%' . $db->escapeLike('easeweaa') . '%', 'LIKE')
          ->condition('first.field_user_first_name_value', '%' . $db->escapeLike('for you, a bonus of 100') . '%')
      )
      ->execute()
      ->fetchCol();

    // Build a list of tables to be cleaned.
    $tables = [
      'sessions' => 'uid',
      'users' => 'uid',
      'users_data' => 'uid',
      'users_field_data' => 'uid',
    ];
    $field_tables = $db->query("SHOW TABLES LIKE :user_field", [
      ':user_field' => $db->escapeLike('user__') . '%',
    ])->fetchCol();
    $tables += array_fill_keys($field_tables, 'entity_id');
    $sandbox['tables'] = $tables;

    // Init the account deletion progress.
    $sandbox['progress'] = 0;
  }

  $uids_to_delete = array_splice($sandbox['uids'], 0, 1000);
  foreach ($sandbox['tables'] as $table => $id_field) {
    $db->delete($table)
      ->condition($id_field, $uids_to_delete, 'IN')
      ->execute();
  }

  // Delete items from Apache Solr 'unpublished' index.
  // @todo Find out why blocked users are indexed in 'unpublished' index and, if
  // case, remove and avoid indexing them.
  // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-5296
  Index::load('unpublished')->trackItemsDeleted(
    'entity:user',
    array_map(function (string $uid): string {
      return "$uid:en";
    }, $uids_to_delete)
  );

  $deleted_count = count($uids_to_delete);
  $sandbox['progress'] += $deleted_count;

  $sandbox['#finished'] = $sandbox['uids'] ? 0 : 1;

  if ($sandbox['uids']) {
    return "Just deleted {$deleted_count} spam accounts. Continuing...";
  }
  else {
    return "Finished deleting {$sandbox['progress']} spam accounts.";
  }
}

/**
 * Reset the user default icon.
 */
function joinup_user_post_update_reset_default_icons() {
  include_once __DIR__ . '/joinup_user.install';
  joinup_user_setup_default_avatar();
}

/**
 * Set default frequency to 'Weekly' for all existing users.
 */
function joinup_user_post_update_set_default_frequency(array &$sandbox) {
  $database = \Drupal::database();

  if (empty($sandbox['uids'])) {
    $sandbox['progress'] = 0;

    $select = $database->select('users', 'u');
    $select->leftJoin('user__field_user_frequency', 'f', 'u.uid = f.entity_id');
    $select->fields('u', ['uid'])
      ->isNull('f.field_user_frequency_value')
      ->condition('u.uid', 0, '!=');
    $sandbox['uids'] = $select->execute()->fetchCol();
  }

  $uids = array_splice($sandbox['uids'], 0, 1000);
  foreach ($uids as $uid) {
    $fields = [
      'bundle' => 'user',
      'deleted' => '0',
      'entity_id' => $uid,
      'revision_id' => $uid,
      'langcode' => 'en',
      'delta' => 0,
      'field_user_frequency_value' => 'weekly',
    ];
    $database->insert('user__field_user_frequency')->fields($fields)->execute();
  }

  $count = count($uids);
  $sandbox['progress'] += $count;

  $sandbox['#finished'] = $sandbox['uids'] ? 0 : 1;
  if ($sandbox['uids']) {
    return "Updated {$count} accounts. Continuing...";
  }
  else {
    return "Finished updating {$sandbox['progress']} accounts.";
  }
}

/**
 * Unsubscribe all members from their collections.
 */
function joinup_user_post_update_unsubscribe_all_members() {
  $database = \Drupal::database();
  $database->truncate('og_membership__subscription_bundles')->execute();
}

/**
 * Fix broken accounts with empty emails.
 */
function joinup_user_post_update_fix_broken_accounts(): void {
  // Delete test accounts without email.
  foreach (['test_cancel', 'test_cancel_mod'] as $user_name) {
    if ($user = user_load_by_name($user_name)) {
      $user->delete();
    }
  }

  $user_storage = \Drupal::entityTypeManager()->getStorage('user');
  $uids = $user_storage
    ->getQuery()
    ->notExists('mail')
    ->condition('uid', 0, '!=')
    ->execute();

  /** @var \Drupal\user\UserInterface $user */
  foreach ($user_storage->loadMultiple($uids) as $user) {
    $mail = $user->getAccountName() . '@example.com';
    $user->setEmail($mail);
    $user->save();
  }
}
