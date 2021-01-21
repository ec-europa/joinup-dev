<?php

/**
 * @file
 * Post update functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API but
 * need to run _before_ the configuration is imported.
 *
 * For example this can be used to enable a new module that needs to have its
 * code available for the configuration to be successfully imported or updated.
 *
 * In most cases though update code should be placed in joinup_core.deploy.php.
 */

declare(strict_types = 1);

use Drupal\Core\Batch\Percentage;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_subscription\Entity\GroupContentSubscriptionMessage;
use Drupal\message\MessageInterface;

/**
 * Remove digest messages that are already sent.
 */
function joinup_core_post_update_0106800(array &$sandbox): TranslatableMarkup {
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
function joinup_core_post_update_0106801(array &$sandbox): TranslatableMarkup {
  $database = \Drupal::database();
  $storage = \Drupal::entityTypeManager()->getStorage('message');

  // Initialize the sandbox and results counter on first run.
  if (!isset($sandbox['ids'])) {
    // The message template and its field were not created yet as this script
    // runs before config sync. Let's do it here.
    $config_names = [
      'message.template.group_content_subscription' => 'message_template',
      'field.storage.message.field_group_content' => 'field_storage_config',
      'field.field.message.group_content_subscription.field_group_content' => 'field_config',
    ];
    $config_sync_dir = Settings::get('config_sync_directory');
    $entity_type_manager = \Drupal::entityTypeManager();
    foreach ($config_names as $config_name => $entity_type_id) {
      $values = Yaml::decode(file_get_contents("{$config_sync_dir}/{$config_name}.yml"));
      $entity_type_manager->getStorage($entity_type_id)->create($values)->save();
    }

    // Get unsent message digests.
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
