<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Re-import the fixtures and fix existing solutions.
 */
function joinup_core_post_update_0106200(&$sandbox) {
  // Clean up the existing graph.
  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $sparql_connection->query('WITH <http://eira_skos> DELETE { ?s ?p ?o } WHERE { ?s ?p ?o } ');

  // Re import the file to update the terms.
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);

  $filepath = __DIR__ . '/../../../../resources/fixtures/EIRA_SKOS.rdf';
  $graph = new Graph('http://eira_skos');
  $graph->parse(file_get_contents($filepath));
  $graph_store->insert($graph);

  // Repeat steps taken after importing the fixtures that target eira terms.
  // @see: \Joinup\Phing\AfterFixturesImportCleanup::main
  $sparql_connection->query('WITH <http://eira_skos> INSERT { ?subject a skos:Concept } WHERE { ?subject a skos:Collection . };');
  $sparql_connection->query('WITH <http://eira_skos> INSERT INTO <http://eira_skos> { ?subject skos:topConceptOf <http://data.europa.eu/dr8> } WHERE { ?subject a skos:Concept .};');
  $sparql_connection->query('WITH <http://eira_skos> INSERT { ?member skos:broaderTransitive ?collection } WHERE { ?collection a skos:Collection . ?collection skos:member ?member };');
}

/**
 * Fix the last updated time of node entities.
 */
function joinup_core_post_update_0106201(&$sandbox) {
  // In Joinup, all node updates through the UI always create a new revision.
  // Only updates through the API can update an entity without creating a new
  // revision. However, after moving the visit_count outside the storage, there
  // is no other functionality that can perform such a task.
  // Thus, it is safe to assume, that the "changed" property of each revision is
  // the same as the "revision_timestamp". The following query will fix all
  // cases where the entity was updated by an automatic procedure that wasn't
  // actually touching any values.
  $query = <<<QUERY
UPDATE {node_field_revision} nfr
INNER JOIN {node_revision} nr ON nfr.vid = nr.vid
SET nfr.changed = nr.revision_timestamp
WHERE nfr.changed != nr.revision_timestamp
QUERY;

  \Drupal::database()->query($query);
}
