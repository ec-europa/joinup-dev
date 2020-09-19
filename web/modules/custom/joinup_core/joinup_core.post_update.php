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
 * Remove path alias duplicates.
 */
function joinup_core_post_update_0106401(?array &$sandbox = NULL): string {
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

  $to_delete = array_splice($sandbox['duplicate_pids'], 0, 1000);
  $db->delete('path_alias_revision')
    ->condition('id', $to_delete, 'IN')
    ->execute();
  $db->delete('path_alias')
    ->condition('id', $to_delete, 'IN')
    ->execute();
  $sandbox['progress'] += count($to_delete);

  if ($sandbox['#finished'] = (int) empty($sandbox['duplicate_pids'])) {
    \Drupal::entityTypeManager()->getStorage('path_alias')->resetCache();
  }

  return "Removed {$sandbox['progress']}/{$sandbox['total']}";
}

/**
 * Update aliases for entities with the old alias.
 */
function joinup_core_post_update_0106402(?array &$sandbox = NULL): string {
  $rdf_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  if (empty($sandbox['entity_ids'])) {
    $sandbox['entity_ids'] = $rdf_storage->getQuery()->execute();
    $sandbox['count'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $entity_ids = array_splice($sandbox['entity_ids'], 0, 100);

  $alias_generator = \Drupal::getContainer()->get('pathauto.generator');
  foreach ($rdf_storage->loadMultiple($entity_ids) as $entity) {
    // Update aliases for the entity's default language and its translations.
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      /** @var \Drupal\Core\Entity\TranslatableInterface $translated_entity */
      $translated_entity = $entity->getTranslation($langcode);
      $alias_generator->createEntityAlias($translated_entity, 'bulkupdate');
    }
  }

  $sandbox['count'] += count($entity_ids);
  $sandbox['#finished'] = (int) empty($sandbox['entity_ids']);
  return "Processed {$sandbox['count']}/{$sandbox['max']}";
}

/**
 * Re-insert the new EIF vocabularies to apply new changes.
 */
function joinup_core_post_update_0106403(): void {
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
