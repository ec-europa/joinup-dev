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

use Drupal\asset_distribution\Entity\DownloadEvent;

/**
 * Restore the field policy domain node field into the new topic field.
 */
function joinup_core_deploy_0107000(&$sandbox) {
  $schema = \Drupal::database()->schema();
  $type = [
    'type' => 'varchar',
    'length' => 128,
    'not null' => TRUE,
    'description' => 'The ID of the target entity.',
  ];

  $schema->dropTable('node__field_topic');
  $schema->renameTable('node__field_topic_backup', 'node__field_topic');
  // The "changeField" happens in the deploy phase so that we can use the API
  // to perform the changes because the command to change the field name differs
  // even from mariaDB to MySQL.
  $schema->changeField('node__field_topic', 'field_policy_domain_target_id', 'field_topic_target_id', $type);
  $schema->dropTable('node_revision__field_topic');
  $schema->renameTable('node_revision__field_topic_backup', 'node_revision__field_topic');
  $schema->changeField('node_revision__field_topic', 'field_policy_domain_target_id', 'field_topic_target_id', $type);

  $sparql_endpoint = \Drupal::getContainer()->get('sparql.endpoint');

  $query = <<<QUERY
DELETE { GRAPH <http://topic> { ?entity <http://www.w3.org/2004/02/skos/core#inScheme> <http://joinup.eu/policy-domain> } }
INSERT { GRAPH <http://topic> { ?entity <http://www.w3.org/2004/02/skos/core#inScheme> <http://joinup.eu/vocabulary/topic> } }
WHERE { GRAPH <http://topic> { ?entity <http://www.w3.org/2004/02/skos/core#inScheme> <http://joinup.eu/policy-domain> } }
QUERY;
  $sparql_endpoint->query($query);

  // Update the references from <http://joinup.eu/policy-domain> to
  // <http://joinup.eu/vocabulary/topic> in all graphs that have the
  // topic reference.
  $query = <<<QUERY
DELETE { GRAPH ?g { ?entity <http://policy_domain> ?value } }
INSERT { GRAPH ?g { ?entity <http://joinup.eu/vocabulary/topic> ?value } }
WHERE { GRAPH ?g { ?entity <http://policy_domain> ?value } }
QUERY;
  $sparql_endpoint->query($query);
}

/**
 * Switch the filter format of the collection abstract to basic HTML.
 */
function joinup_core_deploy_0107001(array &$sandbox): string {
  $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');

  if (!isset($sandbox['total'])) {
    $query = $storage->getQuery()
      ->condition('rid', 'collection')
      ->exists('field_ar_abstract');
    $sandbox['ids'] = array_values($query->execute());
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['processed'] = 0;
  }

  $ids = array_splice($sandbox['ids'], 0, 19);
  /** @var \Drupal\collection\Entity\CollectionInterface[] $collections */
  $collections = $storage->loadMultiple($ids);
  foreach ($collections as $collection) {
    $collection->field_ar_abstract->format = 'basic_html';
    $collection->save();

  }
  $sandbox['processed'] += count($ids);
  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : $sandbox['processed'] / $sandbox['total'];

  return "Processed {$sandbox['processed']} out of {$sandbox['total']}";
}

/**
 * Fill the parent of the distribution downloads.
 */
function joinup_core_deploy_0107002(array &$sandbox): string {
  if (empty($sandbox['entity_ids'])) {
    $sandbox['entity_ids'] = \Drupal::database()->query('SELECT `id` FROM joinup_download_event')->fetchCol();
    $sandbox['progress'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $file_usage = \Drupal::getContainer()->get('file.usage');
  $sparql_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  $entity_ids = array_splice($sandbox['entity_ids'], 0, 100);
  /** @var \Drupal\asset_distribution\Entity\DownloadEvent $download_event */
  foreach (DownloadEvent::loadMultiple($entity_ids) as $download_event) {
    $file = $download_event->file->entity;
    if (empty($file)) {
      continue;
    }

    $usages = $file_usage->listUsage($file);
    if (empty($usages['file']['rdf_entity'])) {
      continue;
    }
    $distribution = $sparql_storage->load(key($usages['file']['rdf_entity']));
    if (empty($distribution)) {
      continue;
    }

    try {
      $parent = $distribution->getParent();
    }
    catch (\Exception $e) {
      // We don't want to force anything for old records.
      continue;
    }

    $download_event->set('parent_entity_type', 'rdf_entity');
    $download_event->set('parent_entity_id', $parent->id());
    $download_event->save();
  }

  $sandbox['progress'] += count($entity_ids);
  $sandbox['#finished'] = empty($sandbox['entity_ids']) ? 1 : (float) $sandbox['progress'] / (float) $sandbox['max'];

  return "Completed {$sandbox['progress']} out of {$sandbox['max']}.";
}
