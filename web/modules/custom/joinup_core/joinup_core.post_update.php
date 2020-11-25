<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Update the EIRA SKOS file and its references.
 */
function joinup_core_post_update_0106600(array &$sandbox): void {
  $graphs = [
    'http://joinup.eu/solution/draft',
    'http://joinup.eu/solution/published',
  ];
  $connection = \Drupal::getContainer()->get('sparql.endpoint');
  foreach ($graphs as $graph) {
    $update_query = <<<QUERY
WITH <{$graph}>
DELETE { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/Ontologies> }
INSERT { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/Ontology> }
WHERE { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/Ontologies> }
QUERY;
    $connection->query($update_query);
  }

  $graph_name = 'http://eira_skos';
  $connection->query("DEFINE sql:log-enable 3 CLEAR GRAPH <$graph_name>;");
  $connection_options = $connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);
  $filepath = __DIR__ . '/../../../../resources/fixtures/EIRA_SKOS.rdf';
  $graph = new Graph($graph_name);
  $graph->parse(file_get_contents($filepath));
  $graph_store->insert($graph);
}
