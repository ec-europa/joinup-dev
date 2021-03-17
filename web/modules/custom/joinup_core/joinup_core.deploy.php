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

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Delete stale tables.
 */
function joinup_core_deploy_0106900(array &$sandbox): string {
  $tables = [
    'config_sync_snapshot_active',
    'config_sync_snapshot_extension',
    'config_sync_merged',
    'old_2d7f64menu_link_content',
    'old_2d7f64menu_link_content_data',
    'old_5e332d_url_alias',
  ];

  foreach ($tables as $table) {
    \Drupal::database()->schema()->dropTable($table);
  }

  return 'Deleted tables.';
}

/**
 * Fix the datatype of the owner ID in owners and contact information.
 */
function joinup_core_deploy_0106901(array &$sandbox): void {
  $database = \Drupal::getContainer()->get('sparql.endpoint');
  $variables = [
    'http://joinup.eu/owner/published' => 'http://joinup.eu/owner/uid',
    'http://joinup.eu/contact_information/published' => 'http://joinup.eu/contact_information/uid',
  ];

  foreach ($variables as $graph => $predicate) {
    $query = <<<QUERY
WITH <{$graph}>
DELETE { ?owner <{$predicate}> ?value }
INSERT { ?owner <{$predicate}> ?new_value }
WHERE {
 ?owner <{$predicate}> ?value .
 FILTER (datatype(?value) = <integer>) .
 BIND(STRDT(STR(?value), xsd:integer) AS ?new_value)
}
QUERY;

    $database->query($query);
  }
}

/**
 * Moves the data about the content listing of custom pages to paragraphs (2).
 */
function joinup_core_deploy_0106902(array &$sandbox): string {
  if (empty($sandbox['items'])) {
    $state = \Drupal::state();
    $sandbox['items'] = $state->get('isaicp_5880');
    $state->delete('isaicp_5880');
    $sandbox['progress'] = 0;
    $sandbox['count'] = count($sandbox['items']);
    $sandbox['updated'] = 0;
  }

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $items = array_splice($sandbox['items'], 0, 20);
  // Refactor the array to be keyed by 'nid' and having 'listing' as value.
  $items = array_combine(array_column($items, 'nid'), array_column($items, 'listing'));

  foreach ($node_storage->loadMultiple(array_keys($items)) as $nid => $custom_page) {
    $paragraph = Paragraph::create(['type' => 'content_listing']);
    $cp_value = [0 => ['value' => $items[$nid]]];
    $paragraph->set('field_content_listing', $cp_value)->save();

    $paragraphs_body = $custom_page->get('field_paragraphs_body');
    $value = $paragraphs_body->getValue();
    $value[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
    $paragraphs_body->setValue($value);
    $custom_page->save();
    $sandbox['updated']++;
  }

  $sandbox['progress'] += count($items);
  $sandbox['#finished'] = (int) empty($sandbox['items']);

  return "Updated {$sandbox['progress']} out of {$sandbox['count']} [{$sandbox['updated']} were updated]";
}

/**
 * Update URL aliases of group content with short ID.
 */
function joinup_core_deploy_0106903(array &$sandbox): string {
  $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  $updater = \Drupal::getContainer()->get('joinup_group.url_alias_updater');

  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = $storage->getQuery()
      ->condition('rid', ['collection', 'solution'], 'IN')
      ->exists('field_short_id')
      ->execute();
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['progress'] = 0;
  }

  $ids = array_splice($sandbox['ids'], 0, 10);
  /** @var \Drupal\joinup_group\Entity\GroupInterface[] $groups */
  $groups = $storage->loadMultiple($ids);
  foreach ($groups as $group) {
    $updater->queueGroupContent($group);
  }
  $sandbox['progress'] += count($ids);
  $sandbox['#finished'] = (int) empty($sandbox['ids']);

  return "Processed {$sandbox['progress']} out of {$sandbox['total']}";
}
