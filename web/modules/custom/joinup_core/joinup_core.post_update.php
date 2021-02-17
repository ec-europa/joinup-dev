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
 * Moves the data about the content listing of custom pages to paragraphs (1).
 */
function joinup_core_post_update_0106802(array &$sandbox): void {
  $db = \Drupal::database();
  $query = <<<Query
SELECT cl.entity_id AS nid, cl.field_cp_content_listing_value AS listing
FROM {node__field_cp_content_listing} cl
INNER JOIN {node_field_data} n ON cl.entity_id  = n.nid
ORDER BY nid
Query;
  $items = $db->query($query)->fetchAll();

  $items = array_filter(
    array_map(
      function (\stdClass $item): array {
        $item->listing = unserialize($item->listing);
        $item->listing['fields']['field_content_listing_type'] = $item->listing['fields']['field_cp_content_listing_content_type'];
        unset($item->listing['fields']['field_cp_content_listing_content_type']);
        return (array) $item;
      },
      $items
    ),
    function (array $item): bool {
      // Skip if there is the field is not enabled and there are no query
      // presets, meaning that the field is not simply disabled.
      return $item['listing']['enabled'] != 0 || !empty($item['listing']['query_presets']);
    }
  );

  \Drupal::state()->set('isaicp_5880', $items);
  $db->truncate('node__field_cp_content_listing');
  $db->truncate('node_revision__field_cp_content_listing');
}
