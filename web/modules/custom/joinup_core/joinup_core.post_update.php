<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
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
/**
 * Migrate data about pinned entities into meta entities.
 */
function joinup_core_post_update_0106401(): void {
  $entity_type_manager = \Drupal::entityTypeManager();

  $get_storage = function (string $entity_type_id) use ($entity_type_manager) {
    return $entity_type_manager->getStorage($entity_type_id);
  };

  $entity_ids = [];

  // Retrieve community content to migrate.
  $entity_ids['node'] = $get_storage('node')
    ->getQuery()
    ->condition('type', CommunityContentHelper::BUNDLES, 'IN')
    ->condition('sticky', TRUE)
    ->execute();

  // Retrieve solutions to migrate.
  $entity_ids['rdf_entity'] = $get_storage('rdf_entity')
    ->getQuery()
    ->condition('rid', 'solution')
    ->exists('field_is_pinned_in')
    ->execute();

  foreach ($entity_ids as $entity_type_id => $ids) {
    $storage = $get_storage($entity_type_id);
    foreach ($storage->loadMultiple($ids) as $entity) {
      if ($entity instanceof PinnableGroupContentInterface) {
        if ($entity instanceof CommunityContentInterface) {
          $entity->pin();
        }
        else {
          /** @var \Drupal\joinup_group\Entity\GroupInterface[] $pinned_groups */
          $pinned_groups = $entity->get('field_is_pinned_in')->referencedEntities();
          foreach ($pinned_groups as $pinned_group) {
            $entity->pin($pinned_group);
          }
        }
      }
    }
  }
}
