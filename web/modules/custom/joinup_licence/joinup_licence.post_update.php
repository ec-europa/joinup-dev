<?php

/**
 * @file
 * Post update functions for the Joinup licence module.
 */

use Drupal\Core\Database\Database;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Update import the spdx legal type vocabulary.
 */
function joinup_licence_post_update_import_legal_type_vocabulary() {
  $graph_uri = 'http://licence-legal-type';
  /** @var \Drupal\Driver\Database\joinup_sparql\Connection $connection */
  $connection = \Drupal::service('sparql_endpoint');
  // Avoid duplicates in case a manual fixtures import has already occurred.
  $connection->query("CLEAR GRAPH <{$graph_uri}>;");
  $graph = new Graph($graph_uri);
  $filename = DRUPAL_ROOT . '/../resources/fixtures/licence-legal-type.rdf';
  $graph->parseFile($filename);

  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);

  $graph_store->insert($graph);
}
