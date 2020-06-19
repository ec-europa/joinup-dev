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

  // Update existing solutions.
  $query = <<<QUERY
DELETE { GRAPH ?g { ?s <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/PublicPolicyImplementationApproach> } }
INSERT { GRAPH ?g { ?s <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/InteroperableDigitalPublicServicesImplementationOrientation> } }
WHERE { GRAPH ?g { ?s <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/PublicPolicyImplementationApproach> } }
QUERY;

  $sparql_connection->query($query);
}
