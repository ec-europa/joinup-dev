<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Re-insert the new EIF vocabularies to apply new changes.
 */
function joinup_core_post_update_0106401(): void {
  $vids = [
    'eif_conceptual_model',
    'eif_interoperability_layer',
    'eif_principle',
    'eif_recommendation',
  ];

  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);
  foreach ($vids as $vocabulary) {
    $filepath = __DIR__ . "/../../../../resources/fixtures/{$vocabulary}.rdf";
    $graph_uri = "http://{$vocabulary}";
    $graph = new Graph($graph_uri);
    $sparql_connection->update("CLEAR GRAPH <{$graph_uri}>");
    $graph->parse(file_get_contents($filepath));
    $graph_store->insert($graph);
  }

  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage('taxonomy_term');
  $tids = $storage->getQuery()->condition('vid', $vids, 'IN')->execute();
  /** @var \Drupal\taxonomy\TermInterface $term */
  foreach ($storage->loadMultiple($tids) as $term) {
    ContentEntity::indexEntity($term);
  }
  /** @var \Drupal\search_api\IndexInterface $index */
  $index = $entity_type_manager->getStorage('search_api_index')->load('published');
  $index->reindex();
  $index->indexItems(-1, 'entity:taxonomy_term');
}

/**
 * Delete all persistent aliases to ensure that they will be rebuilt.
 */
function joinup_core_post_update_0106402(): void {
  \Drupal::keyValue('pathauto_state.rdf_entity')->deleteAll();
  \Drupal::keyValue('pathauto_state.node')->deleteAll();
}

/**
 * Remove path alias duplicates.
 */
function joinup_core_post_update_0106403(?array &$sandbox = NULL): string {
  $db = \Drupal::database();
  if (!isset($sandbox['duplicate_pids'])) {
    // Get all duplicate path alias IDs.
    $sandbox['duplicate_pids'] = $db->query("SELECT p.id
    FROM {path_alias} p
    LEFT JOIN (
      -- This sub-query returns all alias duplicates of English aliases.
      SELECT
        MAX(id) AS valid_id,
        COUNT(*) AS duplicates_count,
        path
      FROM {path_alias}
      WHERE langcode = 'en'
      GROUP BY path
      HAVING duplicates_count > 1
    ) valid_aliases ON p.path = valid_aliases.path
    WHERE valid_aliases.valid_id IS NOT NULL
    AND p.id <> valid_aliases.valid_id
    -- Only select English aliases.
    AND p.langcode = 'en'")->fetchCol();
    $sandbox['progress'] = 0;
    $sandbox['total'] = count($sandbox['duplicate_pids']);
  }

  if ($to_delete = array_splice($sandbox['duplicate_pids'], 0, 1000)) {
    $db->delete('path_alias_revision')
      ->condition('id', $to_delete, 'IN')
      ->execute();
    $db->delete('path_alias')
      ->condition('id', $to_delete, 'IN')
      ->execute();
  }
  $sandbox['progress'] += count($to_delete);

  if ($sandbox['#finished'] = (int) empty($sandbox['duplicate_pids'])) {
    \Drupal::entityTypeManager()->getStorage('path_alias')->resetCache();
  }

  return "Removed {$sandbox['progress']}/{$sandbox['total']}";
}

/**
 * Update aliases for entities with the old alias.
 */
function joinup_core_post_update_0106404(?array &$sandbox = NULL): string {
  $rdf_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  if (empty($sandbox['entity_ids'])) {
    // We process first the collections, solutions and releases because they're
    // parents for the rest of entities and their alias is used in children
    // alias computing.
    $sandbox['entity_ids']['rdf_entity'] = $rdf_storage->getQuery()->condition('rid', 'collection')->execute();
    $sandbox['entity_ids']['rdf_entity'] += $rdf_storage->getQuery()->condition('rid', 'solution')->execute();
    $sandbox['entity_ids']['rdf_entity'] += $rdf_storage->getQuery()->condition('rid', 'asset_release')->execute();
    $sandbox['entity_ids']['rdf_entity'] += $rdf_storage->getQuery()->condition('rid', [
      'collection',
      'solution',
      'asset_release',
    ], 'NOT IN')->execute();
    $sandbox['entity_ids']['node'] = $node_storage->getQuery()->execute();
    $sandbox['count'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']['rdf_entity']) + count($sandbox['entity_ids']['node']);
  }

  if (empty($sandbox['entity_ids']['rdf_entity'])) {
    $storage = $node_storage;
    $entity_ids = array_splice($sandbox['entity_ids']['node'], 0, 100);
  }
  else {
    $storage = $rdf_storage;
    $entity_ids = array_splice($sandbox['entity_ids']['rdf_entity'], 0, 100);
  }

  $alias_generator = \Drupal::getContainer()->get('pathauto.generator');
  foreach ($storage->loadMultiple($entity_ids) as $entity) {
    if ($entity->bundle() === 'asset_release' && $entity->field_isr_release_number->isEmpty()) {
      // There are a number of releases that do not have a release number, even
      // though it is a mandatory field. Aliases fail to be created for these
      // entities. These will be handled in ISAICP-6217.
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6217
      continue;
    }
    $alias_generator->updateEntityAlias($entity, 'bulkupdate');
  }

  $sandbox['count'] += count($entity_ids);
  $sandbox['#finished'] = (int) (empty($sandbox['entity_ids']['rdf_entity']) && empty($sandbox['entity_ids']['node']));
  return "Processed {$sandbox['count']}/{$sandbox['max']}";
}
