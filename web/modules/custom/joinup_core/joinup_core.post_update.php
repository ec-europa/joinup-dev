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
function joinup_core_post_update_0106400(): void {
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
