<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
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
 * Migrate data about pinned entities into meta entities (stage 2).
 */
function joinup_core_post_update_0106402(): void {
  $state = \Drupal::state();
  $data = $state->get('joinup_core_update_0106402');
  $state->delete('joinup_core_update_0106402');

  foreach ($data['entity_ids'] as $entity_type_id => $ids) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    foreach ($storage->loadMultiple($ids) as $entity) {
      if ($entity instanceof PinnableGroupContentInterface) {
        if ($entity instanceof CommunityContentInterface) {
          $entity->pin();
        }
        elseif (!empty($data['solutions'][$entity->id()])) {
          foreach ($storage->loadMultiple($data['solutions'][$entity->id()]) as $pinned_group) {
            /** @var \Drupal\joinup_group\Entity\GroupInterface $pinned_group */
            $entity->pin($pinned_group);
          }
        }
      }
    }
  }

  // Remove stale triples.
  $sparql = \Drupal::getContainer()->get('sparql.endpoint');
  foreach (['published', 'draft'] as $status) {
    $sparql->query("WITH <http://joinup.eu/solution/{$status}>
      DELETE { ?s <http://joinup.eu/solution/pinned_in> ?o }
      WHERE { ?s <http://joinup.eu/solution/pinned_in> ?o }");
  }
}
