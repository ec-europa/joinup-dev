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
use Drupal\node\Entity\Node;

/**
 * Convert 'newsletter' nodes into 'document' nodes.
 */
function joinup_core_post_update_0106800(): void {
  // Run this as post-update, in order to fix content before deleting the node
  // type, in config synchronization. Avoid performance issues by running direct
  // DB queries, instead of using entity API.
  $db = \Drupal::database();

  // Collect newsletter node IDs.
  $query = $db->select('node', 'n');
  $query->join('node_field_data', 'nfd', 'n.nid = nfd.nid');
  $newsletters = $query->fields('n', ['nid', 'vid'])
    ->fields('nfd', ['status'])
    ->condition('n.type', 'newsletter')
    ->execute()
    ->fetchAll();

  // Convert existing fields.
  $tables = [
    'node' => 'type',
    'node_field_data' => 'type',
    'node__body' => 'bundle',
    'node_revision__body' => 'bundle',
    'node__og_audience' => 'bundle',
    'node_revision__og_audience' => 'bundle',
  ];
  foreach ($tables as $table => $field) {
    $db->update($table)
      ->fields([$field => 'document'])
      ->condition($field, 'newsletter')
      ->execute();
  }
  $db->truncate('node__simplenews_issue')->execute();
  $db->truncate('node_revision__simplenews_issue')->execute();
  $db->truncate('simplenews_subscriber__subscriptions')->execute();
  $db->truncate('simplenews_mail_spool')->execute();
  $db->truncate('simplenews_subscriber')->execute();

  $fields = [
    'bundle',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'field_type_value',
  ];

  // Fill the document type field.
  foreach (['node__field_type', 'node_revision__field_type'] as $table) {
    $insert = $db->insert($table)->fields($fields);
    foreach ($newsletters as $newsletter) {
      $insert->values(array_combine($fields, [
        'document',
        $newsletter->nid,
        $newsletter->vid,
        'en',
        0,
        'newsletter',
      ]));
    }
    $insert->execute();
  }

  // Set an appropriate workflow state, depending on the publication state.
  $fields = [
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'field_state_value',
  ];

  foreach (['node__field_state', 'node_revision__field_state'] as $table) {
    $insert = $db->insert($table)->fields($fields);
    foreach ($newsletters as $newsletter) {
      $insert->values(array_combine($fields, [
        'document',
        0,
        $newsletter->nid,
        $newsletter->vid,
        'en',
        0,
        $newsletter->status == 1 ? 'validated' : 'draft',
      ]));
    }
    $insert->execute();
  }

  // Re-generate aliases.
  $alias_generator = \Drupal::getContainer()->get('pathauto.generator');
  foreach (Node::loadMultiple(array_keys($newsletters)) as $node) {
    $alias_generator->updateEntityAlias($node, 'bulkupdate');
  }
}

/**
 * Convert glossary abbreviation into term synonym (stage 1).
 */
function joinup_core_post_update_0106801(): void {
  $db = \Drupal::database();
  $query = $db->select('node__field_glossary_abbreviation', 'ga');
  $query->addExpression('ga.entity_id', 'nid');
  $query->addExpression('ga.field_glossary_abbreviation_value', 'abbr');
  $query->innerJoin('node_field_data', 'n', 'ga.entity_id = n.nid');
  $terms = $query
    // Exclude abbreviations same as their term title.
    ->where('LOWER(ga.field_glossary_abbreviation_value) <> LOWER(n.title)')
    ->execute()
    ->fetchAll();
  \Drupal::state()->set('isaicp_6153', $terms);
  $db->truncate('node__field_glossary_abbreviation');
  $db->truncate('node_revision__field_glossary_abbreviation');
}

/**
 * Remove digest messages that are already sent.
 */
function joinup_core_post_update_0106802(array &$sandbox): TranslatableMarkup {
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
function joinup_core_post_update_0106803(array &$sandbox): TranslatableMarkup {
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
