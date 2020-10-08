<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

/**
 * Clean up the validation graphs.
 */
function joinup_core_post_update_0106500(array &$sandbox): void {
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
 * Add creation time to entities solutions that lack it.
 */
function joinup_core_post_update_0106501(array &$sandbox): void {
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
