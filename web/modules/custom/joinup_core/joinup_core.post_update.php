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

use Drupal\node\Entity\Node;

/**
 * Convert 'newsletter' nodes into 'document' nodes.
 */
function joinup_core_post_update_0106700(): void {
  // Run this as post-update, in order to fix content before deleting the node
  // type, in config synchronization. Avoid performance issues by running direct
  // DB queries, instead of using entity API.
  $db = \Drupal::database();

  // Collect newsletter node IDs.
  $newsletters = $db->select('node')
    ->fields('node', ['nid', 'vid'])
    ->condition('type', 'newsletter')
    ->execute()
    ->fetchAllKeyed();

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
    foreach ($newsletters as $nid => $vid) {
      $insert->values(array_combine($fields, [
        'document',
        $nid,
        $vid,
        'en',
        0,
        'newsletter',
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
