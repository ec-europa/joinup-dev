<?php

/**
 * @file
 * Deploy functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API and
 * need to run _after_ the configuration is imported.
 *
 * This is applicable in most cases. However in case the update code enables
 * some functionality that is required for configuration to be successfully
 * imported, it should instead be placed in joinup_core.post_update.php.
 */

declare(strict_types = 1);

use Drupal\Core\Batch\Percentage;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\honeypot\ExpiredRecordsDeleter;
use Drupal\joinup_subscription\Entity\GroupContentSubscriptionMessage;
use Drupal\message\MessageInterface;

/**
 * Delete expired Honeypot records.
 */
function joinup_core_deploy_0106700(&$sandbox): TranslatableMarkup {
  // Initialize the sandbox and results counter on first run.
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = ExpiredRecordsDeleter::getExpiredRecordCount();
  }
  if (!isset($sandbox['current'])) {
    $sandbox['current'] = 0;
  }

  if ($sandbox['total'] == 0) {
    $sandbox['#finished'] = 1;
    return t('There are no expired records to delete.');
  }

  $database = \Drupal::database();
  $query = $database->select('key_value_expire', 'kve');
  $query->fields('kve', ['name']);
  $query->condition('collection', 'honeypot_time_restriction');
  $query->condition('expire', 2147483647);
  $query->range(0, 50000);
  $result = $query->execute();

  $deleted_count = $database->delete('key_value_expire')
    ->condition('collection', 'honeypot_time_restriction')
    ->condition('name', $result->fetchCol(), 'IN')
    ->execute();

  if ($deleted_count == 0) {
    $sandbox['#finished'] = 1;
    return t('All expired records have been deleted.');
  }

  $sandbox['current'] += $deleted_count;
  $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];
  return t('Deleted @deleted of @total records (@percentage% complete)', [
    '@deleted' => $sandbox['current'],
    '@total' => $sandbox['total'],
    '@percentage' => Percentage::format($sandbox['total'], $sandbox['current']),
  ]);
}

/**
 * Fix the EIF recommendation menu link route.
 */
function joinup_core_deploy_0106701(): void {
  \Drupal::entityTypeManager()->getStorage('menu_link_content')->load(11390)
    ->set('link', 'route:view.eif_recommendation.all;rdf_entity=http_e_f_fdata_ceuropa_ceu_fw21_f405d8980_b3f06_b4494_bb34a_b46c388a38651')
    ->save();
}

/**
 * Remove digest messages that are already sent.
 */
function joinup_core_deploy_0106702(&$sandbox): TranslatableMarkup {
  $database = \Drupal::database();
  $storage = \Drupal::entityTypeManager()->getStorage('message');

  // Initialize the sandbox and results counter on first run.
  if (!isset($sandbox['mids'])) {
    $query = $database->select('message_digest', 'md');
    $query->fields('md', ['mid']);
    $query->condition('md.sent', 1);
    $sandbox['mids'] = $query->execute()->fetchCol();
    $sandbox['total'] = count($sandbox['mids']);
    $sandbox['current'] = 0;
  }

  if ($sandbox['total'] == 0) {
    $sandbox['#finished'] = 1;
    return t('There are no digest messages to delete.');
  }

  $mids_to_delete = array_slice($sandbox['mids'], $sandbox['current'], 500);
  $messages_to_delete = $storage->loadMultiple($mids_to_delete);
  $storage->delete($messages_to_delete);
  $sandbox['current'] += count($mids_to_delete);
  $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];

  return t('Deleted @deleted of @total digest messages (@percentage% complete)', [
    '@deleted' => $sandbox['current'],
    '@total' => $sandbox['total'],
    '@percentage' => Percentage::format($sandbox['total'], $sandbox['current']),
  ]);
}

/**
 * Convert existing collection digest messages to group digest messages.
 */
function joinup_core_deploy_0106703(&$sandbox): TranslatableMarkup {
  $database = \Drupal::database();
  $storage = \Drupal::entityTypeManager()->getStorage('message');

  // Initialize the sandbox and results counter on first run.
  if (!isset($sandbox['ids'])) {
    $query = $database->select('message_digest', 'md');
    $query->fields('md', ['id', 'mid']);
    $query->condition('md.sent', 0);
    $sandbox['ids'] = $query->execute()->fetchAll();
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['current'] = 0;
  }

  if ($sandbox['total'] == 0) {
    $sandbox['#finished'] = 1;
    return t('There are no digest messages to convert.');
  }

  $records = array_slice($sandbox['ids'], $sandbox['current'], 20);
  foreach ($records as $record) {
    $message_to_convert = $storage->load($record->mid);
    if ($message_to_convert instanceof MessageInterface) {
      $converted_message = GroupContentSubscriptionMessage::create([
        'uid' => $message_to_convert->uid->target_id,
        'created' => $message_to_convert->created->value,
        'field_group_content' => $message_to_convert->get('field_collection_content')->first()->getValue(),
      ]);
      $converted_message->save();

      // Update the existing message digest record to reference the new message.
      $database->update('message_digest')
        ->condition('id', $record->id)
        ->fields(['mid' => $converted_message->id()])
        ->execute();

      // Delete the old message.
      $message_to_convert->delete();
    }
    else {
      // If a message digest record is orphaned, delete it.
      $database->delete('message_digest')
        ->condition('id', $record->id)
        ->execute();
    }
    $sandbox['current']++;
  }

  $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];

  return t('Converted @converted of @total digest messages (@percentage% complete)', [
    '@converted' => $sandbox['current'],
    '@total' => $sandbox['total'],
    '@percentage' => Percentage::format($sandbox['total'], $sandbox['current']),
  ]);
}
