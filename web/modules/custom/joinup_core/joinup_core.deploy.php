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
use Drupal\joinup_subscription\Entity\GroupContentSubscriptionMessage;
use Drupal\message\MessageInterface;

/**
 * Remove digest messages that are already sent.
 */
function joinup_core_deploy_0106800(array &$sandbox): TranslatableMarkup {
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
function joinup_core_deploy_0106801(array &$sandbox): TranslatableMarkup {
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
