<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;

/**
 * Delete all persistent aliases to ensure that they will be rebuilt.
 */
function joinup_core_post_update_0106500(): void {
  \Drupal::keyValue('pathauto_state.rdf_entity')->deleteAll();
  \Drupal::keyValue('pathauto_state.node')->deleteAll();
}

/**
 * Remove path alias duplicates.
 */
function joinup_core_post_update_0106501(?array &$sandbox = NULL): string {
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
    ) valid_aliases ON p.path = valid_aliases.path
    WHERE valid_aliases.duplicates_count > 1
    AND valid_aliases.valid_id IS NOT NULL
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
function joinup_core_post_update_0106502(?array &$sandbox = NULL): string {
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

/**
 * Migrate data about pinned entities into meta entities (stage 2).
 */
function joinup_core_post_update_0106503(): void {
  $state = \Drupal::state();
  $data = $state->get('joinup_core_update_0106501');
  $state->delete('joinup_core_update_0106501');

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
        // Commit to Solr backend the items tracked to be indexed.
        \Drupal::service('search_api.post_request_indexing')->destruct();
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

/**
 * Fix the creation date for the RDF graphs.
 */
function joinup_core_post_update_0106504(): void {
  $query = <<<QUERY
WITH <http://joinup.eu/bundle/rdf-graph/graph>
INSERT { ?entity <http://purl.org/dc/terms/issued> ?creation_time }
WHERE { 
  ?entity <http://purl.org/dc/terms/modified> ?creation_time .
  FILTER NOT EXISTS { ?entity <http://purl.org/dc/terms/issued> ?time }
}
QUERY;

  \Drupal::getContainer()->get('sparql.endpoint')->query($query);
}

/**
 * Add creation time to entities solutions that lack it.
 */
function joinup_core_post_update_0106505(array &$sandbox): void {
  // Query the solutions without created date and their provenance records
  // corresponding created date.
  $query = <<<QUERY
SELECT DISTINCT ?graph ?id ?created
WHERE {
  GRAPH ?graph {
    ?id ?p ?o .
    ?id a <http://www.w3.org/ns/dcat#Dataset>
    FILTER NOT EXISTS {?id <http://purl.org/dc/terms/issued> ?created__value} .
    FILTER NOT EXISTS {?id <http://purl.org/dc/terms/isVersionOf> ?field_isr_is_version_of__target_id}
  }
  ?provenance_id a <http://www.w3.org/ns/prov#Activity> .
  ?provenance_id <http://purl.org/dc/terms/issued> ?created .
  ?provenance_id <http://www.w3.org/ns/prov#generated> ?id
}
QUERY;

  $database = \Drupal::getContainer()->get('sparql.endpoint');
  $results = $database->query($query);
  $ids_to_clear = [];

  foreach ($results as $result) {
    $graph = $result->graph->getUri();
    $id = $result->id->getUri();
    $ids_to_clear[] = $id;
    $created = $result->created->toRdfPhp();
    $value = $created['value'];
    $type = $created['datatype'];

    $insert_query = <<<QUERY
WITH <{$graph}>
INSERT { <$id> <http://purl.org/dc/terms/issued> "{$value}"^^<{$type}> }
QUERY;
    $database->query($insert_query);
  }

  \Drupal::entityTypeManager()->getStorage('rdf_entity')->resetCache($ids_to_clear);
}

/**
 * Clean up orphaned triples.
 */
function joinup_core_post_update_0106506(): void {
  $query = <<<QUERY
DELETE { GRAPH ?g { ?s ?p ?o } }
WHERE {
  GRAPH ?g {
    ?s ?p ?o .
    FILTER NOT EXISTS {?s <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?type} .
    VALUES ?g { <http://joinup.eu/asset_distribution/published> <http://joinup.eu/asset_release/published> <http://joinup.eu/asset_release/draft> <http://joinup.eu/collection/published> <http://joinup.eu/collection/draft> <http://joinup.eu/contact-information/published> <http://joinup.eu/licence/published> <http://joinup.eu/owner/published> <http://joinup.eu/provenance_activity> <http://joinup.eu/solution/published> <http://joinup.eu/solution/draft><http://joinup.eu/spdx_licence/published> }
  }
}
QUERY;

  \Drupal::getContainer()->get('sparql.endpoint')->query($query);
}

/**
 * Clean up the validation graphs.
 */
function joinup_core_post_update_0106507(array &$sandbox): void {
  $query = <<<QUERY
SELECT DISTINCT ?g
   WHERE { GRAPH ?g {?s ?p ?o} }
ORDER BY ?g
QUERY;

  $connection = \Drupal::getContainer()->get('sparql.endpoint');
  $graphs = $connection->query($query);
  foreach ($graphs as $graph) {
    $uri = $graph->g->getUri();
    if (strpos($uri, 'http://adms-validator/') === 0) {
      $connection->query("CLEAR GRAPH <$uri>");
    }
  }
}

/**
 * Fix the EIF recommendation menu link route.
 */
function joinup_core_post_update_0106508(): void {
  \Drupal::entityTypeManager()->getStorage('menu_link_content')->load(11390)
    ->set('link', 'route:view.eif_recommendation.all;rdf_entity=http_e_f_fdata_ceuropa_ceu_fw21_f405d8980_b3f06_b4494_bb34a_b46c388a38651')
    ->save();
}
