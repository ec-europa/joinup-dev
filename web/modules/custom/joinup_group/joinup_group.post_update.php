<?php

/**
 * @file
 * Post update functions for the joinup_group module.
 */

declare(strict_types = 1);

use Drupal\joinup_group\ContentCreationOptions;
use EasyRdf\Graph;

/**
 * Migrate eLibrary data to the new Content creation field.
 */
function joinup_group_post_update_migrate_elibrary(): void {
  $predicate_mapping = [
    'http://joinup.eu/collection/elibrary_creation' => 'http://joinup.eu/collection/content_creation',
    'http://joinup.eu/solution/elibrary_creation' => 'http://joinup.eu/solution/content_creation',
  ];
  $elibrary_to_content_creation_mapping = [
    0 => ContentCreationOptions::FACILITATORS,
    1 => ContentCreationOptions::MEMBERS,
    2 => ContentCreationOptions::REGISTERED_USERS,
  ];

  /** @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql */
  $sparql = \Drupal::service('sparql.endpoint');

  // Get all elibrary triples regardless of their graph.
  $query = <<< Query
SELECT ?graph ?entity_id ?predicate ?value
WHERE {
  GRAPH ?graph {
    ?entity_id ?predicate ?value .
    VALUES ?predicate { <http://joinup.eu/collection/elibrary_creation> <http://joinup.eu/solution/elibrary_creation> } .
  }
}
Query;
  $results = $sparql->query($query);

  $graphs = [];

  foreach ($results->getArrayCopy() as $result) {
    $graph_uri = (string) $result->graph;
    // Instead of running a huge INSERT query that will most probably fail, we
    // are using graph objects to store triples for each graph, then we insert
    // the graphs into the graph store.
    if (!isset($graphs[$graph_uri])) {
      $graphs[$graph_uri] = new Graph($graph_uri);
    }
    $graphs[$graph_uri]->add(
      $result->entity_id->getUri(),
      $predicate_mapping[$result->predicate->getUri()],
      $elibrary_to_content_creation_mapping[$result->value->getValue()]
    );
  }

  /** @var \Drupal\joinup_sparql\JoinupSparqlGraphStoreHelperInterface $graph_store_helper */
  $graph_store_helper = \Drupal::service('joinup_sparql.graph_store.helper');
  $graph_store = $graph_store_helper->createGraphStore();

  foreach ($graphs as $graph_uri => $graph) {
    // Delete the legacy elibrary creation triples.
    $query = "WITH <{$graph_uri}>
DELETE { ?s ?p ?o . }
WHERE {
  ?s ?p ?o .
  VALUES ?p { <http://joinup.eu/collection/elibrary_creation> <http://joinup.eu/solution/elibrary_creation> } . 
}";
    $sparql->query($query);

    // Insert the new content creation triples. We're using here the graph store
    // in order to handle a big list of triples. A SPARQL query would have been
    // crashed.
    $graph_store->insert($graph);
  }
}
